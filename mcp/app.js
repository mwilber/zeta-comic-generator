import { ComicGeneratorApi } from "../scripts/modules/ComicGeneratorApi.js";
import { ComicGenerationWorkflow } from "../scripts/modules/ComicGenerationWorkflow.js";
import { ComicRenderer } from "../scripts/modules/ComicRenderer/ComicRenderer.js";
import { McpGenerationProgress } from "./progress.js";
import { installCanvasBalloons } from "./canvas-balloons.js";

installCanvasBalloons();

const APP_PROTOCOL_VERSION = "2026-01-26";
let requestId = 0;
let started = false;
let appOpened = false;
const pending = new Map();
const progress = new McpGenerationProgress();

/**
 * Posts a JSON-RPC envelope to the embedding host.
 *
 * @param {object} message Request, notification, or response envelope.
 * @returns {void}
 */
function post(message) {
	window.parent.postMessage(message, "*");
}

/**
 * Sends a host request and tracks its promise until a matching response arrives.
 *
 * @param {string} method Host JSON-RPC method.
 * @param {object} params Method arguments.
 * @returns {Promise<*>} The host result; rejects when the host returns a JSON-RPC error.
 */
function sendRpc(method, params) {
	return new Promise(
		/**
		 * Registers response handlers before posting the request to the host.
		 *
		 * @param {function(*): void} resolve Completes the request with the host result.
		 * @param {function(*): void} reject Fails the request with the host error.
		 * @returns {void}
		 */
		(resolve, reject) => {
			const id = ++requestId;
			pending.set(id, { resolve, reject });
			post({ jsonrpc: "2.0", id, method, params });
		});
}

/**
 * Sends a host notification without creating a pending request.
 *
 * @param {string} method Notification method.
 * @param {object} [params={}] Notification payload.
 * @returns {void}
 */
function sendNotification(method, params = {}) {
	post({ jsonrpc: "2.0", method, params });
}

/**
 * Closes generation progress and shows an overlay only when there is an error.
 *
 * @param {string} message Error text, or an empty string on success.
 * @returns {void}
 */
function setStatus(message) {
	progress.Finish(message);
}

/**
 * Asks the host to display a follow-up message, logging delivery failures.
 *
 * @param {string} text Message describing the generation result or save choice.
 * @returns {Promise<void>}
 */
async function tellHost(text) {
	try {
		await sendRpc("ui/message", {
			role: "user",
			content: [{ type: "text", text }],
		});
	} catch (error) {
		console.error("Unable to send MCP App follow-up message", error);
	}
}

let sizingReady = false;
let sizeFrame = null;
let lastHeight = null;
/**
 * Schedules one intrinsic-height report after initialization and layout updates.
 *
 * @returns {void}
 */
function reportSize() {
	if (!sizingReady || sizeFrame !== null) return;
	sizeFrame = requestAnimationFrame(
		/**
		 * Measures the settled body height and notifies the host only when it changes.
		 *
		 * @returns {void}
		 */
		() => {
			sizeFrame = null;
			// Measure after responsive CSS and the renderer's resize handler settle.
			// The auto-height body includes padding and can shrink again; the root's
			// scrollHeight is at least the current iframe height and would prevent that.
			const height = Math.ceil(document.body.getBoundingClientRect().height);
			if (height === lastHeight) return;
			lastHeight = height;
			// Let the host own the available width. Request only intrinsic height.
			sendNotification("ui/notifications/size-changed", { height });
		});
}

/**
 * Loads permanent comic data using the same permalink endpoint as the detail page.
 * No generation or save endpoints are used while restoring a comic.
 *
 * @param {string} permalink Saved comic token, retained by the MCP draft record.
 * @param {string} siteBaseUrl Website origin for the detail API and character art.
 * @returns {Promise<void>}
 */
async function loadSavedComic(permalink, siteBaseUrl) {
	if (!/^[a-f0-9]{32}$/.test(permalink)) throw new Error("Invalid saved comic permalink.");
	const baseUrl = siteBaseUrl.replace(/\/$/, "");
	const response = await fetch(`${baseUrl}/api/detail/${permalink}/`, { cache: "no-store" });
	if (!response.ok) throw new Error("Saved comic request failed.");
	const data = await response.json();
	if (data.error || !Array.isArray(data.script?.panels) || data.script.panels.length !== 3
		|| !Array.isArray(data.backgrounds) || data.backgrounds.length !== 3
		|| data.backgrounds.some((url) => typeof url !== "string" || !url)) {
		throw new Error("Saved comic data is incomplete.");
	}

	const script = data.script;
	script.prompt = data.prompt;
	script.series = data.series;
	for (const [index, panel] of script.panels.entries()) {
		if (!Array.isArray(panel.dialog)) panel.dialog = [{ character: "alpha", text: panel.dialog }];
		panel.images = [
			{ url: data.backgrounds[index], type: "background", alt: `Background image: ${panel.background}` },
			{
				url: `${baseUrl}/assets/character_art/${encodeURIComponent(panel.action)}.png`,
				type: "character", character: "alpha", action: panel.action,
				alt: `Character image: alpha in a ${panel.action} pose`,
			},
		];
	}
	const container = document.querySelector(".strip-container");
	container.hidden = false;
	const renderer = new ComicRenderer({ el: container });
	renderer.LoadScript(script);
	setStatus("");
}

/**
 * Checks durable app state before showing anything or permitting generation.
 * The server retains the generation-to-permalink mapping even after draft expiry.
 *
 * @param {object} input Original generate_comic result, possibly replayed by the host.
 * @returns {Promise<void>}
 */
async function openComicApp(input) {
	if (appOpened) return;
	appOpened = true;
	try {
		const result = await sendRpc("tools/call", {
			name: "comic_app_state",
			arguments: { generation_id: input.generation_id, start_generation: true },
		});
		const state = result?.structuredContent;
		if (result?.isError || !state) return;
		if (state.permalink) {
			await loadSavedComic(state.permalink, state.site_base_url);
		} else if (state.generate === true) {
			await generateComic(state);
		}
	} catch (error) {
		console.error("Unable to restore MCP comic app", error);
		document.querySelector(".strip-container").hidden = true;
		setStatus("The comic could not be loaded. Please refresh to try again.");
	} finally {
		reportSize();
	}
}

/**
 * Runs generation once, stages the completed payload, and requests a save choice.
 *
 * @param {object} input Structured result from the generate_comic tool.
 * @param {string} input.generation_id Active draft identifier.
 * @param {string} input.premise User-provided comic premise.
 * @param {string} input.workflow Selected generation provider.
 * @param {string} input.site_base_url Website origin used for API calls and assets.
 * @returns {Promise<void>}
 */
async function generateComic(input) {
	if (started) return;
	started = true;
	document.querySelector(".strip-container").hidden = false;
	progress.Start();

	try {
		const { generation_id: generationId, premise, workflow, site_base_url: siteBaseUrl } = input;
		const renderer = new ComicRenderer({ el: document.querySelector(".strip-container") });
		const api = new ComicGeneratorApi({
			apiBaseUrl: siteBaseUrl,
			assetBaseUrl: siteBaseUrl,
			/**
			 * Updates progress, renders the latest comic, and schedules a height report.
			 *
			 * @param {object} comic Partial or completed comic script.
			 * @param {number} amount API completion percentage.
			 * @returns {void}
			 */
			onUpdate: (comic, amount) => {
				progress.Update(amount);
				renderer.LoadScript(comic);
				reportSize();
			},
		});
		const generation = new ComicGenerationWorkflow({
			api,
			/**
			 * Maps a workflow stage to the progress dialog message.
			 *
			 * @param {string} status Workflow stage identifier.
			 * @returns {void}
			 */
			onStatus: (status) => progress.Stage(status),
		});

		const metrics = await api.GetMetrics();
		if (!metrics || typeof metrics.limitreached !== "boolean") {
			setStatus("Comic generation availability could not be verified.");
			await tellHost("Comic generation availability could not be verified. Please try again later.");
			return;
		}
		if (metrics.limitreached === true) {
			setStatus("The daily comic generation limit has been reached.");
			await tellHost("The daily comic generation limit has been reached. Please try again later.");
			return;
		}

		const result = await generation.Generate(premise, { workflow });
		if (!result || result.error) {
			const rateLimited = result && result.error === "ratelimit";
			setStatus(rateLimited ? "The daily comic generation limit has been reached." : "Comic generation failed.");
			await tellHost(rateLimited
				? "The daily comic generation limit was reached while generating. Please try again later."
				: "Comic generation failed before a complete strip was produced. Please try again.");
			return;
		}

		const staged = await sendRpc("tools/call", {
			name: "stage_comic",
			arguments: {
				generation_id: generationId,
				save_payload: api.GetSavePayload(),
			},
		});
		const draftId = staged?.structuredContent?.draft_id;
		if (!draftId || staged?.isError) {
			throw new Error("The completed comic could not be staged.");
		}

		setStatus("");
		try {
			await sendRpc("ui/update-model-context", {
				content: [{ type: "text", text: `Comic generation finished. Internal save context: draft_id=${draftId}; status=ready_to_save. Use this ID only for save_comic after explicit user confirmation; do not display it.` }],
			});
		} catch (error) {
			// The original generate_comic result also supplies the save ID.
			console.warn("Unable to update comic context", error);
		}
		await tellHost("I like my comic. Save it.");
	} catch (error) {
		console.error("MCP comic generation error", error);
		setStatus("Comic generation failed.");
		await tellHost("The comic app encountered an error before the comic could be saved. Please try again.");
	} finally {
		reportSize();
	}
}

window.addEventListener("message",
	/**
	 * Handles parent-frame responses, generation input, and host context changes.
	 *
	 * @param {MessageEvent} event Incoming postMessage event; other sources are ignored.
	 * @returns {void}
	 */
	(event) => {
		if (event.source !== window.parent) return;
		let message = event.data;
		if (typeof message === "string") {
			try { message = JSON.parse(message); } catch { return; }
		}
		if (!message || typeof message !== "object") return;

		if (message.id !== undefined && pending.has(message.id)) {
			const { resolve, reject } = pending.get(message.id);
			pending.delete(message.id);
			message.error ? reject(message.error) : resolve(message.result);
			return;
		}

		if (message.method === "ui/notifications/tool-result") {
			const result = message.params;
			const input = result?.structuredContent;
			if (input?.available === true && input?.generation_id) {
				openComicApp(input);
			}
		}
		if (message.method === "ui/notifications/host-context-changed") reportSize();
	});

(
	/**
	 * Negotiates the app protocol and enables host size notifications.
	 *
	 * @returns {Promise<void>}
	 */
	async () => {
		try {
			await sendRpc("ui/initialize", {
				protocolVersion: APP_PROTOCOL_VERSION,
				appInfo: { name: "zeta-comic-strip", version: "1.0.0" },
				appCapabilities: { availableDisplayModes: ["inline"] },
			});
		} catch (error) {
			console.error("MCP App initialization failed", error);
			setStatus("The comic app could not connect. Please try again.");
		}
		sendNotification("ui/notifications/initialized");
		sizingReady = true;
		reportSize();
	})();

const sizeObserver = new ResizeObserver(reportSize);
sizeObserver.observe(document.body, { box: "border-box" });
sizeObserver.observe(document.documentElement);
window.addEventListener("resize", reportSize);
