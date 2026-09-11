import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import vm from "node:vm";

const paths = [
	"scripts/modules/GenerationProgressDialog.js",
	"scripts/modules/ComicGenerationWorkflow.js",
	"mcp/progress.js",
	"mcp/app.js",
];
const source = (await Promise.all(paths.map(path => readFile(new URL(`../../${path}`, import.meta.url), "utf8"))))
	.map(text => text.replace(/^import .*;\n/gm, "").replace(/^export /gm, "")).join("\n");

// Exercise the real app/controller/workflow with a small DOM and fake API/host.
async function run(scenario) {
	const history = [];
	let bodyHeight = 320;
	const frames = [];
	const flushFrames = () => { for (const callback of frames.splice(0)) callback(); };
	let onObservedResize;
	const elements = new Map();
	const document = {
		getElementById: id => elements.get(id),
		querySelector: () => elements.get("strip"),
	};
	function element(id) {
		const classes = new Set();
		const attrs = {};
		const el = {
			textContent: "", inert: false,
			classList: { add: value => classes.add(value), remove: value => classes.delete(value), contains: value => classes.has(value) },
			setAttribute(name, value) { attrs[name] = String(value); if (id === "progress" && name === "value") history.push(Number(value)); },
			getAttribute: name => attrs[name],
			querySelector: selector => elements.get(selector === "progress" ? "progress" : "status"),
			focus() { document.activeElement = el; },
			contains: other => other === el,
			getBoundingClientRect: () => ({ width: 600, height: 320 }),
		};
		elements.set(id, el);
		return el;
	}
	for (const id of ["strip", "statusdialog", "status", "progress", "app-status"]) element(id);
	document.body = element("body");
	document.body.getBoundingClientRect = () => ({ width: 600, height: bodyHeight });
	document.documentElement = element("html");
	let listener;
	let onWindowResize;
	const messages = [];
	const parent = { postMessage(message) { messages.push(message); } };
	const window = { parent, addEventListener(type, callback) {
		if (type === "message") listener = callback;
		if (type === "resize") onWindowResize = callback;
	} };
	const respond = (message, result) => listener({ source: parent, data: { id: message.id, result } });
	class Api {
		constructor(options) { this.onUpdate = options.onUpdate; }
		async GetMetrics() { return scenario === "metrics" ? {} : { limitreached: scenario === "limit" }; }
		ClearComicData() { this.comic = {}; }
		async WriteConcept() { this.onUpdate(this.comic, 15); return {}; }
		async WriteScript() { if (scenario === "generation") return { error: "broken" }; this.onUpdate(this.comic, 45); return {}; }
		async WriteBackground() { this.onUpdate(this.comic, 60); return {}; }
		async DrawBackgrounds() { for (const value of [70, 80, 90]) this.onUpdate(this.comic, value); return {}; }
		async DrawAction() { return {}; }
		async WriteContinuity() { this.onUpdate(this.comic, 100); return {}; }
		GetSavePayload() { return {}; }
	}
	const context = vm.createContext({
		document, window, ComicGeneratorApi: Api,
		ComicRenderer: class { LoadScript() { if (scenario === "renderer") throw Error("render failed"); } },
		ResizeObserver: class { constructor(callback) { onObservedResize = callback; } observe() {} }, console: { error() {} },
		requestAnimationFrame: callback => { frames.push(callback); return frames.length; },
		installCanvasBalloons() {},
	});
	vm.runInContext(source, context);
	assert.equal(elements.get("statusdialog").classList.contains("active"), true);
	assert.equal(elements.get("strip").inert, true);
	onObservedResize();
	flushFrames();
	assert.equal(messages.some(m => m.method === "ui/notifications/size-changed"), false, "Wait for the host handshake before reporting size");
	respond(messages.find(m => m.method === "ui/initialize"), {});
	const work = vm.runInContext('generateComic({ generation_id: "test", premise: "test", workflow: "openai", site_base_url: "https://example.test" })', context);
	await new Promise(resolve => setImmediate(resolve));
	flushFrames();
	assert.equal(messages.find(m => m.method === "ui/notifications/size-changed").params.height, 320);
	const staged = messages.find(m => m.method === "tools/call");
	if (["success", "staging"].includes(scenario)) {
		assert.ok(staged);
		assert.equal(elements.get("statusdialog").classList.contains("active"), true, "Keep modal open while staging");
		assert.equal(elements.get("status").textContent, "Preparing your finished comic…");
		assert.deepEqual(history, [0, 15, 45, 60, 70, 80, 90, 100]);
		respond(staged, scenario === "staging" ? { isError: true } : { structuredContent: { draft_id: "draft" } });
		await new Promise(resolve => setImmediate(resolve));
	} else assert.equal(staged, undefined);
	// The host follow-up can stay pending; it must not keep the modal open.
	assert.equal(elements.get("statusdialog").classList.contains("active"), false);
	assert.equal(elements.get("statusdialog").getAttribute("aria-hidden"), "true");
	assert.equal(elements.get("strip").inert, false);
	assert.equal(elements.get("strip").getAttribute("aria-busy"), "false");
	assert.equal(document.activeElement, elements.get("strip"));
	assert.equal(elements.get("app-status").classList.contains("visually-hidden"), false);
	assert.match(elements.get("app-status").textContent, scenario === "success" ? /complete/ : /failed|limit|verified/);
	respond(messages.find(m => m.method === "ui/message"), {});
	await work;
	await vm.runInContext('generateComic({})', context);
	assert.equal(messages.filter(m => m.method === "tools/call").length, staged ? 1 : 0, "Ignore duplicate generation notifications");
	flushFrames();
	const sizes = () => messages.filter(m => m.method === "ui/notifications/size-changed");
	for (const height of [560.25, 220]) {
		onWindowResize();
		onObservedResize();
		bodyHeight = height; // The renderer changes layout before the next frame.
		const before = sizes().length;
		flushFrames();
		assert.equal(sizes().length, before + 1, "Coalesce resize events into one notification");
		assert.equal(sizes().at(-1).params.height, Math.ceil(height), "Report both growth and shrinkage after layout settles");
		assert.equal(sizes().at(-1).params.width, undefined, "Leave width to the host");
		onObservedResize();
		flushFrames();
		assert.equal(sizes().length, before + 1, "Do not loop when host applies the requested height");
	}
}

for (const scenario of ["success", "metrics", "limit", "generation", "renderer", "staging"]) await run(scenario);
console.log("MCP progress lifecycle tests passed.");
