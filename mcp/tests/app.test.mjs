import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import vm from "node:vm";

const source = readFileSync(new URL("../app.js", import.meta.url), "utf8")
	.replace(/^import .*;\n/gm, "");
const generationId = "a".repeat(32);
const permalink = "b".repeat(32);
const baseUrl = "https://comicgenerator.greenzeta.com";
const input = { available: true, generation_id: generationId, site_base_url: baseUrl };

/** Runs the real app controller in a fresh iframe-like context. */
async function mount({ state = { generate: false }, detail, rpcError = false, httpOk = true } = {}) {
	const listeners = {};
	const calls = [];
	const renders = [];
	const requests = [];
	const container = { hidden: true };
	const counts = { progress: 0, metrics: 0, generation: 0 };
	const parent = {
		postMessage(message) {
			calls.push(message);
			if (!message.id) return;
			queueMicrotask(() => {
				const result = message.method === "tools/call"
					? { structuredContent: message.params.name === "comic_app_state" ? state : { draft_id: generationId } }
					: {};
				listeners.message({ source: parent, data: {
					id: message.id,
					...(rpcError && message.params?.name === "comic_app_state" ? { error: { message: "Unavailable" } } : { result }),
				} });
			});
		},
	};
	const context = vm.createContext({
		window: { parent, addEventListener: (name, listener) => { listeners[name] = listener; } },
		document: { querySelector: () => container, body: { getBoundingClientRect: () => ({ height: 0 }) }, documentElement: {} },
		requestAnimationFrame: (callback) => setImmediate(callback),
		ResizeObserver: class { observe() {} },
		console: { error() {}, warn() {} },
		installCanvasBalloons() {},
		McpGenerationProgress: class {
			Start() { counts.progress++; }
			Finish() {}
			Update() {}
			Stage() {}
		},
		ComicRenderer: class { LoadScript(script) { renders.push(script); } },
		ComicGeneratorApi: class {
			async GetMetrics() { counts.metrics++; return { limitreached: false }; }
			GetSavePayload() { return {}; }
		},
		ComicGenerationWorkflow: class { async Generate() { counts.generation++; return {}; } },
		fetch: async (url) => {
			requests.push(url);
			return { ok: httpOk, json: async () => structuredClone(detail) };
		},
	});
	vm.runInContext(source, context);
	assert.equal(container.hidden, true, "Initial view must be hidden");
	assert.equal(counts.progress, 0, "Initialization must not display generation progress");
	const notify = () => listeners.message({ source: parent, data: {
		method: "ui/notifications/tool-result", params: { structuredContent: input },
	} });
	notify();
	notify(); // Hosts can deliver the same result more than once, even before a response.
	for (let n = 0; n < 5; n++) await new Promise(setImmediate);
	return { calls, renders, requests, container, counts };
}

for (const state of [{ generate: false }, {}, { generate: false, permalink: null }]) {
	const app = await mount({ state });
	assert.equal(app.container.hidden, true);
	assert.equal(app.counts.generation, 0);
	assert.equal(app.counts.metrics, 0);
	assert.equal(app.counts.progress, 0);
	assert.equal(app.renders.length, 0);
	assert.equal(app.requests.length, 0);
	assert.equal(app.calls.filter((call) => call.params?.name === "comic_app_state").length, 1);
	assert.equal(app.calls.filter((call) => call.method === "ui/message").length, 0);
}
const first = await mount({ state: { generate: true, generation_id: generationId, premise: "Test", workflow: "google", site_base_url: baseUrl } });
assert.equal(first.counts.generation, 1);
assert.equal(first.counts.metrics, 1);
assert.equal(first.counts.progress, 1);
assert.equal(first.calls.filter((call) => call.params?.name === "stage_comic").length, 1);

const savedState = { generate: false, permalink, site_base_url: baseUrl };
const detail = {
	error: "", prompt: "Saved premise", script: { title: "Saved comic", panels: Array.from({ length: 3 }, () => ({
		dialog: "Hello", action: "standing", background: "Space", images: [{ url: "stale-generation-image" }],
	})) }, backgrounds: [1, 2, 3].map((n) => `https://example.test/${n}.png`),
};
// A second fresh context simulates refresh: only durable server state is supplied.
for (let refresh = 0; refresh < 2; refresh++) {
	const app = await mount({ state: savedState, detail });
	assert.equal(app.container.hidden, false);
	assert.equal(app.counts.generation, 0);
	assert.equal(app.counts.metrics, 0);
	assert.equal(app.counts.progress, 0);
	assert.deepEqual(app.requests, [`${baseUrl}/api/detail/${permalink}/`]);
	assert.equal(app.renders.length, 1);
	assert.equal(app.renders[0].prompt, "Saved premise");
	assert.equal(app.renders[0].panels[0].dialog[0].text, "Hello");
	assert.equal(app.renders[0].panels[0].images[0].url, detail.backgrounds[0]);
	assert.equal(app.renders[0].panels[0].images[1].url, `${baseUrl}/assets/character_art/standing.png`);
	assert.equal(app.calls.filter((call) => call.method === "ui/message" || call.params?.name === "stage_comic").length, 0);
}
for (const options of [
	{ rpcError: true },
	{ state: savedState, detail: { error: "Missing comic" } },
	{ state: savedState, detail, httpOk: false },
	{ state: { ...savedState, permalink: "invalid" }, detail },
]) {
	const app = await mount(options);
	assert.equal(app.container.hidden, true, "Restore failure must keep the comic hidden");
	assert.equal(app.counts.generation, 0, "Restore failure must never fall back to generation");
	assert.equal(app.renders.length, 0);
}
console.log("MCP app reload tests passed.");
