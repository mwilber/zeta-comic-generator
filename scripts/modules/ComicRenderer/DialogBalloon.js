/**
 * @file DialogBalloon.js
 * @author Matthew Wilber
 * @license GPL-3.0
 *
 * A static class with functions to render an image containing a cartoon dialog balloon.
 *
 */
export class DialogBalloon {
	/**
	 * Renders a dialog balloon image with the provided parameters.
	 * This method uses a <canvas> element to draw the balloon and outputs the image via the canvas toDataURL method.
	 *
	 * @param {string} dialog - The text to display in the dialog balloon.
	 * @param {object} params - An object containing the following properties:
	 * @param {number} [params.size=512] - The size of the canvas and resulting image.
	 * @param {object} [params.center={x: size/2, y: size/8}] - The center position of the dialog balloon.
	 * @param {object} [params.pointer={x: size/3, y: size/5}] - The position of the pointer on the dialog balloon.
	 * @returns {Promise<HTMLImageElement>} - A Promise that resolves to an HTMLImageElement representing the rendered dialog balloon.
	 */
	static async RenderImage(dialog, params) {
		if (!dialog.split) return;
		let { size, center, pointer } = params;
		const balloon = document.createElement("canvas");

		// Set default values if not provided
		size = size || 512;
		center = center || { x: size / 2, y: size / 8 };
		pointer = pointer || { x: size / 3, y: size / 5 };

		balloon.width = params.size || 512;
		balloon.height = params.size || 512;
		const ctx = balloon.getContext("2d");

		// Load the google font
		// TODO: Make this customizable
		var myFont = new FontFace(
			"Patrick Hand",
			"url(https://fonts.gstatic.com/s/patrickhand/v23/LDI1apSQOAYtSuYWp8ZhfYe8XsLLubg58w.woff2)"
		);
		const font = await myFont.load();
		document.fonts.add(font);

		this.drawBalloon(
			ctx,
			dialog.split("").join(String.fromCharCode(8202)),
			center.x,
			center.y,
			pointer.x,
			pointer.y
		);

		var image = new Image();
		image.classList.add("balloon");
		image.src = balloon.toDataURL();

		return image;
	}

	/**
	 * Draws a dialog balloon with a pointer on a canvas.
	 *
	 * @param {CanvasRenderingContext2D} ctx - The 2D rendering context of the canvas.
	 * @param {string} dialog - The text to be displayed in the dialog balloon.
	 * @param {number} cx - The x-coordinate of the center of the dialog balloon.
	 * @param {number} cy - The y-coordinate of the center of the dialog balloon.
	 * @param {number} px - The x-coordinate of the pointer location.
	 * @param {number} py - The y-coordinate of the pointer location.
	 */
	static drawBalloon(ctx, dialog, cx, cy, px, py) {
		const maxWidth = 502;
		const padding = 15;
		const fontSize = 24;
		const pointerWidth = 15;
		const borderRadius = 20;
		const lineHeight = fontSize * 1.2;

		ctx.fillStyle = "white";
		ctx.strokeStyle = "black";
		ctx.lineWidth = 2;
		ctx.font = `${fontSize}px 'Patrick Hand'`;
		ctx.textBaseline = "top";

		// Function to get lines from text
		function getLines(ctx, text, maxWidth) {
			const words = text.split(" ");
			const lines = [];
			let currentLine = words[0];

			for (let i = 1; i < words.length; i++) {
				const word = words[i];
				const width = ctx.measureText(currentLine + " " + word).width;
				if (width < maxWidth) {
					currentLine += " " + word;
				} else {
					lines.push(currentLine);
					currentLine = word;
				}
			}
			lines.push(currentLine);
			return lines;
		}

		const lines = getLines(ctx, dialog, maxWidth - padding * 2);
		// Fixed from ChatGPT session, original code calculated width using only the first line.
		let boxWidth = lines.reduce(
			(topWidth, line) =>
				Math.max(ctx.measureText(line).width + padding * 2, topWidth),
			0
		);
		const boxHeight = lineHeight * lines.length + padding * 1.5;
		const borderWidth = ctx.lineWidth;

		// Lower the balloon when the pointer is long
		// const tdist = Math.sqrt((cx - px) * (cx - px) + ((cy + boxHeight / 2 ) - py) * ((cy + boxHeight / 2 ) - py));
		// if(tdist > 100) cy += (tdist - 100) * 0.5;

		// Calculate balloon position based on cx and cy
		const balloonX = cx - boxWidth / 2;
		const balloonY = cy;

		// Handle a balloon that hangs lower than the pointer location
		if (py < balloonY + boxHeight + 10) py = balloonY + boxHeight + 50;

		const baseCenterX = cx;
		const baseCenterY = cy + boxHeight / 2;

		const dx = baseCenterX - px;
		const dy = baseCenterY - py;
		const dist = Math.sqrt(dx * dx + dy * dy);

		let adjPointerWidth = pointerWidth * (125 / dist);

		const baseOffsetX = (adjPointerWidth * dy) / dist; // Offset along the balloon border
		const baseOffsetY = (adjPointerWidth * dx) / dist; // Offset along the balloon border

		const baseLeftX = baseCenterX + baseOffsetX;
		const baseLeftY = baseCenterY - baseOffsetY;
		const baseRightX = baseCenterX - baseOffsetX;
		const baseRightY = baseCenterY + baseOffsetY;

		// Draw pointer border (only sides of the pointer, not the base)
		ctx.beginPath();
		ctx.moveTo(baseLeftX, baseLeftY);
		ctx.lineTo(px, py);
		ctx.lineTo(baseRightX, baseRightY);
		ctx.lineWidth *= 2;
		ctx.stroke();
		ctx.lineWidth *= 0.5;

		// Draw balloon
		ctx.beginPath();
		ctx.moveTo(balloonX + borderRadius, balloonY);
		ctx.lineTo(balloonX + boxWidth - borderRadius, balloonY); // Top side
		ctx.quadraticCurveTo(
			balloonX + boxWidth,
			balloonY,
			balloonX + boxWidth,
			balloonY + borderRadius
		); // Top-right corner
		ctx.lineTo(balloonX + boxWidth, balloonY + boxHeight - borderRadius); // Right side
		ctx.quadraticCurveTo(
			balloonX + boxWidth,
			balloonY + boxHeight,
			balloonX + boxWidth - borderRadius,
			balloonY + boxHeight
		); // Bottom-right corner
		ctx.lineTo(balloonX + borderRadius, balloonY + boxHeight); // Bottom side
		ctx.quadraticCurveTo(
			balloonX,
			balloonY + boxHeight,
			balloonX,
			balloonY + boxHeight - borderRadius
		); // Bottom-left corner
		ctx.lineTo(balloonX, balloonY + borderRadius); // Left side
		ctx.quadraticCurveTo(
			balloonX,
			balloonY,
			balloonX + borderRadius,
			balloonY
		); // Top-left corner
		ctx.closePath();
		ctx.fill();
		ctx.stroke();

		// Draw pointer as a solid white shape with no border
		ctx.fillStyle = "white";
		ctx.beginPath();
		ctx.moveTo(baseLeftX, baseLeftY);
		ctx.lineTo(px, py);
		ctx.lineTo(baseRightX, baseRightY);
		ctx.closePath();
		ctx.fill();

		// Draw text
		ctx.fillStyle = "black";
		for (let i = 0; i < lines.length; i++) {
			ctx.fillText(
				lines[i],
				balloonX + padding,
				balloonY + padding + i * lineHeight
			);
		}
	}
}
