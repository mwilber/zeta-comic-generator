import { ComicGeneratorApi } from "../scripts/modules/ComicGeneratorApi.js";
import { ComicGenerationWorkflow } from "../scripts/modules/ComicGenerationWorkflow.js";
import { ComicRenderer } from "../scripts/modules/ComicRenderer/ComicRenderer.js";
import { McpGenerationProgress } from "./progress.js";

const APP_PROTOCOL_VERSION = "2026-01-26";
let requestId = 0;
let started = false;
const pending = new Map();
const progress = new McpGenerationProgress();
progress.Start();

function post(message) {
	window.parent.postMessage(message, "*");
}

function sendRpc(method, params) {
	return new Promise((resolve, reject) => {
		const id = ++requestId;
		pending.set(id, { resolve, reject });
		post({ jsonrpc: "2.0", id, method, params });
	});
}

function sendNotification(method, params = {}) {
	post({ jsonrpc: "2.0", method, params });
}

function setStatus(message) {
	progress.Finish(message);
}

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

let lastWidth = 0;
let lastHeight = 0;
function reportSize() {
	const strip = document.getElementById("strip");
	const body = document.body.getBoundingClientRect();
	const stripBounds = strip.getBoundingClientRect();
	const width = Math.ceil(stripBounds.width + 16);
	const height = Math.ceil(body.height);
	if (width === lastWidth && height === lastHeight) return;
	lastWidth = width;
	lastHeight = height;
	sendNotification("ui/notifications/size-changed", { width, height });
}

async function generateComic(input) {
	if (started) return;
	started = true;

	try {
		const { generation_id: generationId, premise, workflow, site_base_url: siteBaseUrl } = input;
		const renderer = new ComicRenderer({ el: document.querySelector(".strip-container") });
		const api = new ComicGeneratorApi({
			apiBaseUrl: siteBaseUrl,
			assetBaseUrl: siteBaseUrl,
			onUpdate: (comic, amount) => {
				progress.Update(amount);
				renderer.LoadScript(comic);
				reportSize();
			},
		});
		const generation = new ComicGenerationWorkflow({
			api,
			onStatus: (status) => progress.Stage(status),
		});

		const metrics = await api.GetMetrics();
		if (!metrics || typeof metrics.limitreached !== "boolean") {
			setStatus("Comic generation availability could not be verified.");
			await tellHost("Comic generation availability could not be verified. Please inform me that I should try again later.");
			return;
		}
		if (metrics.limitreached === true) {
			setStatus("The daily comic generation limit has been reached.");
			await tellHost("The daily comic generation limit has been reached. Please inform me that I should try again later.");
			return;
		}

		const result = await generation.Generate(premise, { workflow });
		if (!result || result.error) {
			const rateLimited = result && result.error === "ratelimit";
			setStatus(rateLimited ? "The daily comic generation limit has been reached." : "Comic generation failed.");
			await tellHost(rateLimited
				? "The daily comic generation limit was reached while generating. Please inform me that I should try again later."
				: "Comic generation failed before a complete strip was produced. Please let me know and suggest trying again.");
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

		setStatus("Comic generation complete. Waiting for save choice.");
		await tellHost(`The comic is complete and visible in the app. Its draft_id is ${draftId}. Ask me whether I want to save it. Do not call save_comic unless I explicitly say yes.`);
	} catch (error) {
		console.error("MCP comic generation error", error);
		setStatus("Comic generation failed.");
		await tellHost("The comic app encountered an error before the comic could be saved. Please let me know and suggest trying again.");
	} finally {
		reportSize();
	}
}

window.addEventListener("message", (event) => {
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
			generateComic(input);
		}
	}
});

(async () => {
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
	reportSize();
})();

new ResizeObserver(reportSize).observe(document.body);
