import { DialogBalloon } from "../scripts/modules/ComicRenderer/DialogBalloon.js";

let balloonFont;

/**
 * Draws an accessible inline canvas using the shared balloon drawing routine.
 *
 * @param {string} dialog Dialogue text.
 * @param {object} [params={}] Balloon dimensions and placement.
 * @param {number} [params.size=512] Canvas width and height in pixels.
 * @param {object} [params.center] Balloon origin with x and y coordinates.
 * @param {object} [params.pointer] Pointer endpoint with x and y coordinates.
 * @returns {Promise<HTMLCanvasElement|undefined>} Rendered canvas, or undefined for non-string dialogue.
 */
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
	).load().then(
		/**
		 * Registers the loaded handwriting font for canvas text rendering.
		 *
		 * @param {FontFace} font Successfully loaded balloon font.
		 * @returns {FontFaceSet} The document font collection.
		 */
		font => document.fonts.add(font)).catch(
			/**
			 * Logs a font failure so balloon drawing can continue with a fallback.
			 *
			 * @param {Error} error Font loading failure.
			 * @returns {void}
			 */
			error => {
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

/**
 * Installs inline canvas rendering in the MCP iframe without editing shared modules.
 *
 * @returns {void}
 */
export function installCanvasBalloons() {
	// ComicRenderer calls this static method directly and accepts any DOM node.
	// Adapt that hook only in the MCP iframe; the website module stays untouched.
	DialogBalloon.RenderImage = renderCanvasBalloon;
}
