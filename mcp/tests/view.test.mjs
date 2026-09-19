import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import vm from "node:vm";

const source = ["../saved-comic.js", "../view.js"]
	.map((path) => readFileSync(new URL(path, import.meta.url), "utf8")
		.replace(/^import .*;\n/gm, "").replace(/^export /gm, ""))
	.join("\n");
const permalink = "b".repeat(32);
const baseUrl = "https://comicgenerator.greenzeta.com";
const input = { permalink, site_base_url: baseUrl };
const detail = {
	error: "", prompt: "Saved premise", series: { title: "Space" },
	script: { title: "Saved comic", panels: Array.from({ length: 3 }, (_, i) => ({
		dialog: i === 0 ? "Hello" : [{ character: "alpha", text: "World" }],
		action: "standing", background: "Space", images: [{ url: "stale-image" }],
	})) },
	backgrounds: [1, 2, 3].map((n) => `https://example.test/${n}.png`),
};

/** Exercises the real viewer and loader with only host, network, and renderer mocks. */
async function mount({ data = detail, toolInput = input, httpOk = true, networkError = false, isError = false } = {}) {
	const listeners = {};
	const calls = [];
	const requests = [];
	const renders = [];
	const container = { hidden: true };
	const status = {
		textContent: "Loading saved comic…",
		append(...nodes) {
			this.children = nodes;
			this.textContent += nodes.map((node) => typeof node === "string" ? node : node.textContent).join("");
		},
	};
	let height = 300;
	const parent = {
		postMessage(message) {
			calls.push(message);
			if (message.method === "ui/initialize") queueMicrotask(() => {
				listeners.message({ source: parent, data: { id: message.id, result: {} } });
			});
		},
	};
	vm.runInNewContext(source, {
		window: { parent, addEventListener: (name, listener) => { listeners[name] = listener; } },
		document: {
			createElement: (tagName) => ({ tagName }),
			querySelector: (selector) => selector === "#app-status" ? status : container,
			body: { getBoundingClientRect: () => ({ height }) }, documentElement: {},
		},
		requestAnimationFrame: (callback) => setImmediate(callback),
		ResizeObserver: class { observe() {} },
		console: { error() {} },
		installCanvasBalloons() {},
		ComicRenderer: class { LoadScript(script) { renders.push(script); } },
		fetch: async (url, options) => {
			requests.push({ url, options });
			if (networkError) throw new Error("Offline");
			return { ok: httpOk, json: async () => structuredClone(data) };
		},
	});
	assert.equal(container.hidden, true);
	const message = { method: "ui/notifications/tool-result", params: { structuredContent: toolInput, isError } };
	listeners.message({ source: {}, data: message });
	assert.equal(requests.length, 0, "Other frames cannot start a load");
	listeners.message({ source: parent, data: "invalid JSON" });
	listeners.message({ source: parent, data: JSON.stringify(message) });
	listeners.message({ source: parent, data: message });
	for (let n = 0; n < 5; n++) await new Promise(setImmediate);
	assert.equal(calls.filter((call) => call.method === "tools/call").length, 0, "Viewer never calls draft, generation, or saving tools");
	assert.equal(calls.filter((call) => call.method === "ui/notifications/initialized").length, 1);
	assert.equal(calls.find((call) => call.method === "ui/notifications/size-changed").params.height, 300);
	height = 180;
	listeners.resize();
	await new Promise(setImmediate);
	assert.equal(calls.at(-1).params.height, 180, "Host height can shrink with the layout");
	return { calls, requests, renders, container, status };
}

// A new iframe uses only the original permalink, including after refresh.
for (let refresh = 0; refresh < 2; refresh++) {
	const app = await mount();
	assert.equal(app.container.hidden, false);
	assert.equal(app.status.textContent, "View details about this comic and more on the Zeta Comic Generator website.");
	assert.equal(app.status.children[0].tagName, "a");
	assert.equal(app.status.children[0].textContent, "Zeta Comic Generator website");
	assert.equal(app.status.children[0].href, `${baseUrl}/detail/${permalink}`);
	assert.equal(app.requests.length, 1, "Duplicate results must load only once");
	assert.equal(app.requests[0].url, `${baseUrl}/api/detail/${permalink}/`);
	assert.equal(app.requests[0].options.cache, "no-store");
	assert.equal(app.renders.length, 1);
	const script = app.renders[0];
	assert.equal(script.prompt, detail.prompt);
	assert.equal(script.series.title, detail.series.title);
	assert.equal(script.panels[0].dialog[0].text, "Hello");
	assert.equal(script.panels[1].dialog[0].text, "World");
	for (const [index, panel] of script.panels.entries()) {
		assert.equal(panel.images.length, 2);
		assert.equal(panel.images[0].url, detail.backgrounds[index]);
		assert.equal(panel.images[1].url, `${baseUrl}/assets/character_art/standing.png`);
	}
}
for (const options of [
	{ data: { error: "Comic not found" } },
	{ data: { ...detail, backgrounds: [] } },
	{ data: { ...detail, backgrounds: ["one", null, "three"] } },
	{ data: { ...detail, script: { panels: [] } } },
	{ httpOk: false },
	{ networkError: true },
	{ toolInput: { ...input, permalink: `${baseUrl}/detail/${permalink}` } },
	{ toolInput: { ...input, permalink: "42" } },
	{ toolInput: { ...input, permalink: permalink + "\n" } },
	{ toolInput: {} },
	{ isError: true },
]) {
	const app = await mount(options);
	assert.equal(app.container.hidden, true);
	assert.equal(app.renders.length, 0);
	assert.match(app.status.textContent, /could not be loaded|valid saved comic/);
}
console.log("Saved comic viewer tests passed.");
