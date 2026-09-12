import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import vm from "node:vm";

const paths = [
	"scripts/modules/GenerationProgressDialog.js",
	"scripts/modules/ComicGenerationWorkflow.js",
	"mcp/progress.js",
	"mcp/app.js",
];
const source = (await Promise.all(paths.map(
	/**
	 * Reads a module used by the isolated app test runtime.
	 *
	 * @param {string} path Project-relative JavaScript path.
	 * @returns {Promise<string>} Module source.
	 */
	path => readFile(new URL(`../../${path}`, import.meta.url), "utf8"))))
	.map(
		/**
		 * Removes module syntax so test sources can run together in a VM context.
		 *
		 * @param {string} text Module source.
		 * @returns {string} Bundle-compatible source.
		 */
		text => text.replace(/^import .*;\n/gm, "").replace(/^export /gm, "")).join("\n");

// Exercise the real app/controller/workflow with a small DOM and fake API/host.
/**
 * Checks app progress, error recovery, and height reporting for one scenario.
 *
 * @param {string} scenario Success or failure case to simulate.
 * @returns {Promise<void>}
 */
async function run(scenario) {
	const history = [];
	let bodyHeight = 320;
	const frames = [];
	const flushFrames =
		/**
		 * Runs and clears the currently queued animation-frame callbacks.
		 *
		 * @returns {void}
		 */
		() => { for (const callback of frames.splice(0)) callback(); };
	let onObservedResize;
	const elements = new Map();
	const document = {
		/**
		 * Looks up a fake DOM element by its fixture identifier.
		 *
		 * @param {string} id Fixture identifier.
		 * @returns {object|undefined} Matching element.
		 */
		getElementById: id => elements.get(id),
		/**
		 * Returns the strip fixture for the app renderer selector.
		 *
		 * @returns {object} The strip element.
		 */
		querySelector: () => elements.get("strip"),
	};
	/**
	 * Creates a minimal DOM fixture and registers it by identifier.
	 *
	 * @param {string} id Element identifier.
	 * @returns {object} The fake element.
	 */
	function element(id) {
		const classes = new Set();
		const attrs = {};
		const el = {
			textContent: "", inert: false,
			classList: {
				/**
				 * Adds a class to the fixture class set.
				 *
				 * @param {string} value Class name.
				 * @returns {Set<string>} Updated class set.
				 */
				add: value => classes.add(value),
				/**
				 * Removes a class from the fixture class set.
				 *
				 * @param {string} value Class name.
				 * @returns {boolean} Whether the class was present.
				 */
				remove: value => classes.delete(value),
				/**
				 * Checks whether the fixture has the requested class.
				 *
				 * @param {string} value Class name.
				 * @returns {boolean} Whether the class is present.
				 */
				contains: value => classes.has(value)
			},
			/**
			 * Stores an attribute and records progress value changes.
			 *
			 * @param {string} name Attribute name.
			 * @param {*} value Attribute value, converted to text.
			 * @returns {void}
			 */
			setAttribute(name, value) { attrs[name] = String(value); if (id === "progress" && name === "value") history.push(Number(value)); },
			/**
			 * Reads an attribute stored on the fixture.
			 *
			 * @param {string} name Attribute name.
			 * @returns {string|undefined} Stored attribute.
			 */
			getAttribute: name => attrs[name],
			/**
			 * Resolves progress-dialog child selectors to fixture elements.
			 *
			 * @param {string} selector Child selector used by the shared dialog.
			 * @returns {object} Matching progress or status fixture.
			 */
			querySelector: selector => elements.get(selector === "progress" ? "progress" : "status"),
			/**
			 * Marks this fixture as the active document element.
			 *
			 * @returns {void}
			 */
			focus() { document.activeElement = el; },
			/**
			 * Checks whether the supplied fixture is this element.
			 *
			 * @param {object} other Element to compare.
			 * @returns {boolean} Whether the references match.
			 */
			contains: other => other === el,
			/**
			 * Supplies fixed dimensions for a fake element.
			 *
			 * @returns {object} A rectangle with width and height.
			 */
			getBoundingClientRect: () => ({ width: 600, height: 320 }),
		};
		elements.set(id, el);
		return el;
	}
	for (const id of ["strip", "statusdialog", "status", "progress", "app-status"]) element(id);
	document.body = element("body");
	document.body.getBoundingClientRect =
		/**
		 * Returns the mutable body height used to simulate layout changes.
		 *
		 * @returns {object} A rectangle with width and height.
		 */
		() => ({ width: 600, height: bodyHeight });
	document.documentElement = element("html");
	let listener;
	let onWindowResize;
	const messages = [];
	const parent = {
		/**
		 * Records messages sent to the fake host.
		 *
		 * @param {object} message JSON-RPC envelope.
		 * @returns {void}
		 */
		postMessage(message) { messages.push(message); }
	};
	const window = {
		parent,
		/**
		 * Captures message and resize handlers for manual test dispatch.
		 *
		 * @param {string} type Event type.
		 * @param {Function} callback App event handler.
		 * @returns {void}
		 */
		addEventListener(type, callback) {
			if (type === "message") listener = callback;
			if (type === "resize") onWindowResize = callback;
		}
	};
	const respond =
		/**
		 * Delivers a matching JSON-RPC response from the fake parent frame.
		 *
		 * @param {object} message Original request containing its identifier.
		 * @param {object} result Simulated host result.
		 * @returns {void}
		 */
		(message, result) => listener({ source: parent, data: { id: message.id, result } });
	class Api {
		/**
		 * Captures the update callback passed to the fake generation API.
		 *
		 * @param {object} options API options containing onUpdate.
		 */
		constructor(options) { this.onUpdate = options.onUpdate; }
		/**
		 * Simulates available, rate-limited, or malformed metrics.
		 *
		 * @returns {Promise<object>} Metrics fixture.
		 */
		async GetMetrics() { return scenario === "metrics" ? {} : { limitreached: scenario === "limit" }; }
		/**
		 * Resets the fake comic before running the workflow.
		 *
		 * @returns {void}
		 */
		ClearComicData() { this.comic = {}; }
		/**
		 * Simulates concept completion and reports fifteen percent progress.
		 *
		 * @returns {Promise<object>} Successful step result.
		 */
		async WriteConcept() { this.onUpdate(this.comic, 15); return {}; }
		/**
		 * Simulates script completion or the configured generation failure.
		 *
		 * @returns {Promise<object>} Step result or error.
		 */
		async WriteScript() { if (scenario === "generation") return { error: "broken" }; this.onUpdate(this.comic, 45); return {}; }
		/**
		 * Simulates background descriptions and reports sixty percent progress.
		 *
		 * @returns {Promise<object>} Successful step result.
		 */
		async WriteBackground() { this.onUpdate(this.comic, 60); return {}; }
		/**
		 * Simulates incremental completion of all three background images.
		 *
		 * @returns {Promise<object>} Successful step result.
		 */
		async DrawBackgrounds() { for (const value of [70, 80, 90]) this.onUpdate(this.comic, value); return {}; }
		/**
		 * Simulates successful character rendering.
		 *
		 * @returns {Promise<object>} Successful step result.
		 */
		async DrawAction() { return {}; }
		/**
		 * Simulates continuity completion and reports full progress.
		 *
		 * @returns {Promise<object>} Successful step result.
		 */
		async WriteContinuity() { this.onUpdate(this.comic, 100); return {}; }
		/**
		 * Supplies a minimal payload for the fake staging request.
		 *
		 * @returns {object} Empty save payload fixture.
		 */
		GetSavePayload() { return {}; }
	}
	const context = vm.createContext({
		document, window, ComicGeneratorApi: Api,
		ComicRenderer: class {
			/**
			 * Simulates rendering, throwing only in the renderer-failure scenario.
			 *
			 * @returns {void}
			 */
			LoadScript() { if (scenario === "renderer") throw Error("render failed"); }
		},
		ResizeObserver: class {
			/**
			 * Captures the resize observer callback for manual dispatch.
			 *
			 * @param {Function} callback Callback invoked when layout changes.
			 */
			constructor(callback) { onObservedResize = callback; }
			/**
			 * Accepts observation registration without using a real DOM observer.
			 *
			 * @returns {void}
			 */
			observe() { }
		}, console: {
			/**
			 * Suppresses expected app error logs during failure tests.
			 *
			 * @returns {void}
			 */
			error() { }
		},
		/**
		 * Queues a callback until the test flushes animation frames.
		 *
		 * @param {Function} callback Deferred layout measurement.
		 * @returns {number} Synthetic frame handle.
		 */
		requestAnimationFrame: callback => { frames.push(callback); return frames.length; },
		/**
		 * Skips canvas adapter installation in the isolated progress tests.
		 *
		 * @returns {void}
		 */
		installCanvasBalloons() { },
	});
	vm.runInContext(source, context);
	assert.equal(elements.get("statusdialog").classList.contains("active"), true);
	assert.equal(elements.get("strip").inert, true);
	onObservedResize();
	flushFrames();
	assert.equal(messages.some(
		/**
		 * Identifies a size notification in the recorded host messages.
		 *
		 * @param {object} m Recorded message.
		 * @returns {boolean} Whether this is a size notification.
		 */
		m => m.method === "ui/notifications/size-changed"), false, "Wait for the host handshake before reporting size");
	respond(messages.find(
		/**
		 * Identifies the app initialization request.
		 *
		 * @param {object} m Recorded message.
		 * @returns {boolean} Whether this is an initialization request.
		 */
		m => m.method === "ui/initialize"), {});
	const work = vm.runInContext('generateComic({ generation_id: "test", premise: "test", workflow: "openai", site_base_url: "https://example.test" })', context);
	await new Promise(
		/**
		 * Schedules promise completion on the next Node.js event-loop turn.
		 *
		 * @param {Function} resolve Promise completion callback.
		 * @returns {object} The immediate callback handle.
		 */
		resolve => setImmediate(resolve));
	flushFrames();
	assert.equal(messages.find(
		/**
		 * Identifies a size notification in the recorded host messages.
		 *
		 * @param {object} m Recorded message.
		 * @returns {boolean} Whether this is a size notification.
		 */
		m => m.method === "ui/notifications/size-changed").params.height, 320);
	const staged = messages.find(
		/**
		 * Identifies an app-to-host tool request.
		 *
		 * @param {object} m Recorded message.
		 * @returns {boolean} Whether this is a tool request.
		 */
		m => m.method === "tools/call");
	if (["success", "staging"].includes(scenario)) {
		assert.ok(staged);
		assert.equal(elements.get("statusdialog").classList.contains("active"), true, "Keep modal open while staging");
		assert.equal(elements.get("status").textContent, "Preparing your finished comic…");
		assert.deepEqual(history, [0, 15, 45, 60, 70, 80, 90, 100]);
		respond(staged, scenario === "staging" ? { isError: true } : { structuredContent: { draft_id: "draft" } });
		await new Promise(
			/**
			 * Schedules promise completion on the next Node.js event-loop turn.
			 *
			 * @param {Function} resolve Promise completion callback.
			 * @returns {object} The immediate callback handle.
			 */
			resolve => setImmediate(resolve));
	} else assert.equal(staged, undefined);
	// The host follow-up can stay pending; it must not keep the modal open.
	assert.equal(elements.get("statusdialog").classList.contains("active"), false);
	assert.equal(elements.get("statusdialog").getAttribute("aria-hidden"), "true");
	assert.equal(elements.get("strip").inert, false);
	assert.equal(elements.get("strip").getAttribute("aria-busy"), "false");
	assert.equal(document.activeElement, elements.get("strip"));
	assert.equal(elements.get("app-status").classList.contains("visually-hidden"), scenario === "success");
	assert.match(elements.get("app-status").textContent, scenario === "success" ? /^$/ : /failed|limit|verified/);
	respond(messages.find(
		/**
		 * Identifies the app follow-up message request.
		 *
		 * @param {object} m Recorded message.
		 * @returns {boolean} Whether this is a message request.
		 */
		m => m.method === "ui/message"), {});
	await work;
	await vm.runInContext('generateComic({})', context);
	assert.equal(messages.filter(
		/**
		 * Identifies an app-to-host tool request.
		 *
		 * @param {object} m Recorded message.
		 * @returns {boolean} Whether this is a tool request.
		 */
		m => m.method === "tools/call").length, staged ? 1 : 0, "Ignore duplicate generation notifications");
	flushFrames();
	const sizes =
		/**
		 * Collects height notifications recorded by the fake host.
		 *
		 * @returns {object[]} Recorded size notifications.
		 */
		() => messages.filter(
			/**
			 * Identifies a size notification in the recorded host messages.
			 *
			 * @param {object} m Recorded message.
			 * @returns {boolean} Whether this is a size notification.
			 */
			m => m.method === "ui/notifications/size-changed");
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
