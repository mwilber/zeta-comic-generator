import { ComicRenderer } from "/scripts/modules/ComicRenderer/ComicRenderer.js";
import { ComicExporter } from "/scripts/modules/ComicExporter.js";

const state = {
	permalink: "",
	comic: null,
	renderer: null,
	images: {
		strip: "",
		panels: [],
	},
};

const els = {
	status: document.getElementById("status"),
	stripContainer: document.querySelector(".strip-container"),
	previewStrip: document.getElementById("preview-strip"),
	previewPanels: document.getElementById("preview-panels"),
	postForm: document.getElementById("post-form"),
	postText: document.getElementById("post-text"),
	additionalText: document.getElementById("additional-text"),
	hashtags: document.getElementById("hashtags"),
	postDate: document.getElementById("post-date"),
	submitBtn: document.getElementById("submit-btn"),
	submitResult: document.getElementById("submit-result"),
};

const BASE_URL = window.COMICPROMOTER_CONFIG?.baseUrl || "https://comicgenerator.greenzeta.com";

function setStatus(message) {
	if (els.status) {
		els.status.textContent = message;
	}
}

function setResult(message, isError = false) {
	if (!els.submitResult) return;
	els.submitResult.textContent = message;
	els.submitResult.classList.toggle("error", isError);
	els.submitResult.classList.toggle("success", !isError);
}

function getPermalinkFromUrl() {
	const params = new URLSearchParams(window.location.search);
	return params.get("permalink") || "";
}

async function fetchComic(permalink) {
	const uri = `${BASE_URL}/api/detail/${encodeURIComponent(permalink)}/?c=${Math.floor(Math.random() * 10000)}`;
	const response = await fetch(uri);
	if (!response.ok) throw new Error(`Comic fetch failed (${response.status})`);
	const data = await response.json();
	if (!data || !data.script || !Array.isArray(data.script.panels)) {
		throw new Error("Comic payload missing script panels.");
	}
	return data;
}

function normalizeComicData(data) {
	const script = data.script;
	const backgrounds = Array.isArray(data.backgrounds) ? data.backgrounds : [];

	for (const [idx, panel] of script.panels.entries()) {
		if (!Array.isArray(panel.dialog)) {
			panel.dialog = [{ character: "alpha", text: panel.dialog || "" }];
		}
		panel.images = [];
		if (backgrounds[idx]) {
			panel.images.push({
				url: getProxiedBackgroundUrl(backgrounds[idx]),
				type: "background",
				alt: "Background image",
			});
		}
		panel.images.push({
			url: `/assets/character_art/${panel.action}.png`,
			type: "character",
			character: "alpha",
			action: panel.action,
			alt: `Character image: alpha in a ${panel.action} pose`,
		});
	}

	return {
		id: data.id,
		prompt: data.prompt || "",
		script,
	};
}

function getProxiedBackgroundUrl(url) {
	if (!url) return "";
	if (url.includes("/api/imgproxy/")) return url;
	return `/api/imgproxy/?url=${encodeURIComponent(url)}`;
}

async function waitForImagesToLoad(rootEl) {
	if (!rootEl) return;
	const images = Array.from(rootEl.querySelectorAll("img"));
	await Promise.all(
		images.map(
			(img) =>
				new Promise((resolve) => {
					if (img.complete) return resolve();
					img.addEventListener("load", resolve, { once: true });
					img.addEventListener("error", resolve, { once: true });
				})
		)
	);
}

function renderPreviews() {
	els.previewStrip.innerHTML = "";
	els.previewPanels.innerHTML = "";

	if (state.images.strip) {
		const stripImg = document.createElement("img");
		stripImg.src = state.images.strip;
		stripImg.alt = "Full strip image";
		stripImg.className = "preview-image strip";
		els.previewStrip.appendChild(stripImg);
	}

	state.images.panels.forEach((panelData, idx) => {
		const panelImg = document.createElement("img");
		panelImg.src = panelData;
		panelImg.alt = `Panel ${idx + 1} image`;
		panelImg.className = "preview-image panel";
		els.previewPanels.appendChild(panelImg);
	});
}

function buildComicSummaryForPrompt() {
	const { script, prompt } = state.comic;
	return {
		permalink: state.permalink,
		title: script.title,
		premise: prompt,
		panels: script.panels.map((panel, idx) => ({
			panel: idx + 1,
			scene: panel.scene || "",
			dialog: Array.isArray(panel.dialog)
				? panel.dialog.map((line) => line?.text || "").filter(Boolean)
				: [],
		})),
	};
}

async function generatePostText() {
	const response = await fetch("/api/comicpromoter/generate_post_text.php", {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
		},
		body: JSON.stringify({
			comic: buildComicSummaryForPrompt(),
		}),
	});

	const data = await response.json();
	if (!response.ok || data.error) {
		throw new Error(data.error || "Failed to generate post text.");
	}
	return data.postText || "";
}

async function exportImages() {
	await waitForImagesToLoad(els.stripContainer);

	const stripUrl = await ComicExporter.RenderStripAsDataUrl(state.renderer.el, {
		prompt: state.comic.prompt || "",
		title: state.comic.script.title || "",
		url: "comicgenerator.greenzeta.com",
	});

	const panelUrls = [];
	for (const [idx, panel] of state.comic.script.panels.entries()) {
		const panelUrl = await ComicExporter.RenderPanelAsDataUrl(panel.panelEl, {
			title: idx === 0 ? state.comic.script.title || "" : "",
			url: idx === 2 ? "comicgenerator.greenzeta.com" : "",
		});
		panelUrls.push(panelUrl);
	}

	state.images.strip = stripUrl;
	state.images.panels = panelUrls;
	renderPreviews();
}

function defaultDateInputValue() {
	const now = new Date();
	const yyyy = now.getFullYear();
	const mm = String(now.getMonth() + 1).padStart(2, "0");
	const dd = String(now.getDate()).padStart(2, "0");
	return `${yyyy}-${mm}-${dd}`;
}

function sanitizeControlChars(value) {
	if (typeof value !== "string") return value || "";
	return value.replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/g, "");
}

function dataUrlToBlob(dataUrl) {
	const [meta, encoded] = dataUrl.split(",", 2);
	if (!meta || !encoded) throw new Error("Invalid image data URL.");
	const mimeMatch = meta.match(/^data:(.*?);base64$/);
	const mime = mimeMatch?.[1] || "image/png";
	const binary = atob(encoded);
	const len = binary.length;
	const bytes = new Uint8Array(len);
	for (let i = 0; i < len; i += 1) {
		bytes[i] = binary.charCodeAt(i);
	}
	return new Blob([bytes], { type: mime });
}

async function submitToBuffer(event) {
	event.preventDefault();
	setResult("");

	if (!els.postDate.value) {
		setResult("Please select a date.", true);
		return;
	}

	els.submitBtn.disabled = true;
	setResult("Sending posts to Buffer...");

	try {
		const payload = {
			permalink: state.permalink,
			postTextTemplate: sanitizeControlChars(els.postText.value),
			additionalText: sanitizeControlChars(els.additionalText.value),
			hashtags: sanitizeControlChars(els.hashtags.value),
			date: els.postDate.value,
			images: {
				strip: state.images.strip,
				panels: state.images.panels,
			},
		};
		const formData = new FormData();
		formData.append("permalink", payload.permalink);
		formData.append("postTextTemplate", payload.postTextTemplate);
		formData.append("additionalText", payload.additionalText);
		formData.append("hashtags", payload.hashtags);
		formData.append("date", payload.date);
		formData.append("strip", dataUrlToBlob(payload.images.strip), "strip.png");
		payload.images.panels.forEach((panelDataUrl, idx) => {
			formData.append("panels[]", dataUrlToBlob(panelDataUrl), `panel_${idx + 1}.png`);
		});

		const response = await fetch("/api/comicpromoter/schedule_buffer_posts.php", {
			method: "POST",
			body: formData,
		});

		const data = await response.json();
		if (!response.ok || data.error) {
			throw new Error(data.error || "Buffer scheduling failed.");
		}

		setResult("Success: posts were sent to Buffer.");
	} catch (error) {
		setResult(error.message || "Failed to send posts.", true);
	} finally {
		els.submitBtn.disabled = false;
	}
}

async function init() {
	state.permalink = getPermalinkFromUrl();
	if (!state.permalink) {
		setStatus("Missing required query parameter: permalink");
		els.postForm.style.display = "none";
		return;
	}

	els.postDate.value = defaultDateInputValue();
	els.postForm.addEventListener("submit", submitToBuffer);

	try {
		setStatus("Loading comic data...");
		const detailData = await fetchComic(state.permalink);
		state.comic = normalizeComicData(detailData);

		setStatus("Rendering comic...");
		state.renderer = new ComicRenderer({ el: els.stripContainer });
		state.renderer.LoadScript(state.comic.script);
		await waitForImagesToLoad(els.stripContainer);

		setStatus("Generating full strip and panel images...");
		await exportImages();

		setStatus("Generating social post text with GPT-5.4...");
		const postText = await generatePostText();
		els.postText.value = postText;

		setStatus("Ready");
	} catch (error) {
		setStatus(error.message || "Initialization failed.");
		els.postForm.style.display = "none";
	}
}

document.addEventListener("DOMContentLoaded", init);
