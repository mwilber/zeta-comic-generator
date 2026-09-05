import assert from "node:assert/strict";
import {
	COMIC_WORKFLOWS,
	ComicGenerationWorkflow,
	NormalizeComicWorkflow,
} from "../../scripts/modules/ComicGenerationWorkflow.js";

assert.deepEqual(COMIC_WORKFLOWS.openai, { story: "gpt5", script: "gpt", image: "gptimage" });
assert.deepEqual(COMIC_WORKFLOWS.xai, { story: "grokadv", script: "grok", image: "grokimg" });
assert.deepEqual(COMIC_WORKFLOWS.google, { story: "gemthink", script: "gem", image: "nanobanana" });
assert.equal(NormalizeComicWorkflow("xai"), "xai");
assert.equal(NormalizeComicWorkflow("unsupported"), "openai");

const calls = [];
const api = {
	comic: { title: "Test" },
	ClearComicData() { calls.push(["clear"]); },
	async WriteConcept(premise, options) { calls.push(["concept", premise, options]); return {}; },
	async WriteScript(premise, options) { calls.push(["script", premise, options]); return {}; },
	async WriteBackground(options) { calls.push(["background", options]); return {}; },
	async DrawBackgrounds(options) { calls.push(["images", options]); return {}; },
	async DrawAction() { calls.push(["action"]); return {}; },
	async WriteContinuity(options) { calls.push(["continuity", options]); return {}; },
};
const statuses = [];
const workflow = new ComicGenerationWorkflow({ api, onStatus: (status) => statuses.push(status) });
const result = await workflow.Generate("A tiny test", { workflow: "xai" });

assert.equal(result.workflow, "xai");
assert.deepEqual(calls, [
	["clear"],
	["concept", "A tiny test", { model: "grokadv", seriesId: "" }],
	["script", "A tiny test", { model: "grok" }],
	["background", { model: "grok" }],
	["images", { model: "grokimg", style: "" }],
	["action"],
	["continuity", { model: "grok" }],
]);
assert.deepEqual(statuses, ["concept", "script", "backgrounds", "images", "character", "continuity", "complete"]);

console.log("ComicGenerationWorkflow tests passed.");
