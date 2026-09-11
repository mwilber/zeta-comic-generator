import { DialogBalloon } from "../scripts/modules/ComicRenderer/DialogBalloon.js";

let balloonFont;

async function renderCanvasBalloon(dialog, params = {}) {
	if (typeof dialog !== "string") return;
	const size = params.size || 512;
	const center = params.center || { x: size / 2, y: size / 8 };
	const pointer = params.pointer || { x: size / 3, y: size / 5 };
	const canvas = document.createElement("canvas");
	canvas.width = size;
	canvas.height = size;
	canvas.classList.add("balloon");
	canvas.setAttribute("role", "img");
	canvas.setAttribute("aria-label", "Dialog Balloon: " + dialog);
	canvas.textContent = dialog;

	// Share one font load across panels and incremental renders. A blocked font
	// should still leave readable dialogue using the canvas font fallback.
	balloonFont ??= new FontFace(
		"Patrick Hand",
		"url(https://fonts.gstatic.com/s/patrickhand/v23/LDI1apSQOAYtSuYWp8ZhfYe8XsLLubg58w.woff2)"
	).load().then(font => document.fonts.add(font)).catch(error => {
		console.warn("Unable to load the comic balloon font; using fallback.", error);
	});
	await balloonFont;

	DialogBalloon.drawBalloon(
		canvas.getContext("2d"),
		dialog.split("").join(String.fromCharCode(8202)),
		center.x, center.y, pointer.x, pointer.y,
	);
	return canvas;
}

export function installCanvasBalloons() {
	// ComicRenderer calls this static method directly and accepts any DOM node.
	// Adapt that hook only in the MCP iframe; the website module stays untouched.
	DialogBalloon.RenderImage = renderCanvasBalloon;
}
