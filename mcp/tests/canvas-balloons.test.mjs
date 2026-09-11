import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import vm from "node:vm";

const source = (await readFile(new URL("../canvas-balloons.js", import.meta.url), "utf8"))
	.replace(/^import .*;\n/gm, "").replace(/^export /gm, "");
for (const blockedFont of [false, true]) {
	let loads = 0;
	const draws = [];
	const original = () => { throw Error("Image rendering must not run"); };
	const DialogBalloon = { RenderImage: original, drawBalloon: (...args) => draws.push(args) };
	const context = vm.createContext({
		DialogBalloon,
		Image: class { constructor() { throw Error("Must not create images"); } },
		FontFace: class { async load() { loads++; if (blockedFont) throw Error("blocked"); return this; } },
		document: {
			fonts: { add() {} },
			createElement(tag) {
				assert.equal(tag, "canvas");
				return { tagName: "CANVAS", classList: { add() {} }, attributes: {},
					setAttribute(key, value) { this.attributes[key] = value; },
					getContext: () => "drawing context",
					toDataURL() { throw Error("Must not serialize canvas images"); },
				};
			},
		},
		console: { warn() {} },
	});
	vm.runInContext(source, context);
	assert.equal(DialogBalloon.RenderImage, original, "Import alone should not install the adapter");
	vm.runInContext("installCanvasBalloons()", context);
	const canvases = await Promise.all([
		DialogBalloon.RenderImage("Hello!", { center: {x: 256, y: 10}, pointer: {x: 150, y: 200} }),
		DialogBalloon.RenderImage("Another balloon"),
	]);
	assert.equal(loads, 1, "Share the font promise across concurrent renders");
	assert.equal(draws.length, 2, "Draw balloons even when the font fails");
	assert.deepEqual(draws[0], ["drawing context", "Hello!".split("").join("\u200a"), 256, 10, 150, 200]);
	assert.equal(canvases[0].tagName, "CANVAS");
	assert.equal(canvases[0].width, 512);
	assert.equal(canvases[0].height, 512);
	assert.equal(canvases[0].attributes.role, "img");
	assert.equal(canvases[0].attributes["aria-label"], "Dialog Balloon: Hello!");
	assert.equal(canvases[0].textContent, "Hello!");
	assert.equal(await DialogBalloon.RenderImage(null), undefined);
}
console.log("MCP inline canvas balloon tests passed.");
