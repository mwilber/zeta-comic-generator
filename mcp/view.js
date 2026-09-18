import { loadSavedComic } from "./saved-comic.js";
import { installCanvasBalloons } from "./canvas-balloons.js";

installCanvasBalloons();

let opened = false;
let initialized = false;
let sizeFrame = null;
let lastHeight = null;
const status = document.querySelector("#app-status");

/** Reports intrinsic height after the shared renderer has settled its layout. */
function reportSize() {
	if (!initialized || sizeFrame !== null) return;
	sizeFrame = requestAnimationFrame(() => {
		sizeFrame = null;
		const height = Math.ceil(document.body.getBoundingClientRect().height);
		if (height === lastHeight) return;
		lastHeight = height;
		window.parent.postMessage({
			jsonrpc: "2.0", method: "ui/notifications/size-changed", params: { height },
		}, "*");
	});
}

/** Loads one saved comic per app instance; repeated host results cannot reload it. */
async function openSavedComic(input) {
	if (opened) return;
	opened = true;
	try {
		await loadSavedComic(input.permalink, input.site_base_url);
		status.textContent = "";
	} catch (error) {
		console.error("Unable to load saved comic", error);
		document.querySelector(".strip-container").hidden = true;
		status.textContent = "The saved comic could not be loaded. Check the permalink identifier and try again.";
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
	if (message.id === 1 && !initialized) {
		if (message.error) {
			status.textContent = "The comic viewer could not connect. Please try again.";
			return;
		}
		initialized = true;
		window.parent.postMessage({ jsonrpc: "2.0", method: "ui/notifications/initialized", params: {} }, "*");
		reportSize();
	}
	if (message.method === "ui/notifications/tool-result") {
		if (message.params?.isError) {
			status.textContent = "Provide a valid saved comic permalink identifier, not a URL.";
			reportSize();
		} else if (message.params?.structuredContent) {
			openSavedComic(message.params.structuredContent);
		}
	}
	if (message.method === "ui/notifications/host-context-changed") reportSize();
});

window.parent.postMessage({
	jsonrpc: "2.0", id: 1, method: "ui/initialize",
	params: {
		protocolVersion: "2026-01-26",
		appInfo: { name: "zeta-saved-comic", version: "1.0.0" },
		appCapabilities: { availableDisplayModes: ["inline"] },
	},
}, "*");

const sizeObserver = new ResizeObserver(reportSize);
sizeObserver.observe(document.body, { box: "border-box" });
sizeObserver.observe(document.documentElement);
window.addEventListener("resize", reportSize);
