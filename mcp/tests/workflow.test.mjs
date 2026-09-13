import assert from "node:assert/strict";
import {
	COMIC_WORKFLOWS,
	ComicGenerationWorkflow,
	NormalizeComicWorkflow,
} from "../../scripts/modules/ComicGenerationWorkflow.js";

assert.deepEqual(COMIC_WORKFLOWS.openai, { story: "gpt", script: "gpt5", image: "gptimage" });
assert.deepEqual(COMIC_WORKFLOWS.xai, { story: "grokadv", script: "grok", image: "grokimg" });
assert.deepEqual(COMIC_WORKFLOWS.google, { story: "gemthink", script: "gem", image: "nanobanana" });
assert.equal(NormalizeComicWorkflow("xai"), "xai");
assert.equal(NormalizeComicWorkflow("unsupported"), "openai");

const calls = [];
const api = {
	comic: { title: "Test" },
	/**
	 * Records that the workflow clears existing comic data.
	 *
	 * @returns {void}
	 */
	ClearComicData() { calls.push(["clear"]); },
	/**
	 * Records the concept request and its model selection.
	 *
	 * @param {string} premise Test premise.
	 * @param {object} options Story model and series options.
	 * @returns {Promise<object>} Successful step result.
	 */
	async WriteConcept(premise, options) { calls.push(["concept", premise, options]); return {}; },
	/**
	 * Records the script request and its model selection.
	 *
	 * @param {string} premise Test premise.
	 * @param {object} options Script model options.
	 * @returns {Promise<object>} Successful step result.
	 */
	async WriteScript(premise, options) { calls.push(["script", premise, options]); return {}; },
	/**
	 * Records the background description request.
	 *
	 * @param {object} options Background model options.
	 * @returns {Promise<object>} Successful step result.
	 */
	async WriteBackground(options) { calls.push(["background", options]); return {}; },
	/**
	 * Records the background image request.
	 *
	 * @param {object} options Image model and style options.
	 * @returns {Promise<object>} Successful step result.
	 */
	async DrawBackgrounds(options) { calls.push(["images", options]); return {}; },
	/**
	 * Records the character rendering step.
	 *
	 * @returns {Promise<object>} Successful step result.
	 */
	async DrawAction() { calls.push(["action"]); return {}; },
	/**
	 * Records the continuity request.
	 *
	 * @param {object} options Continuity model options.
	 * @returns {Promise<object>} Successful step result.
	 */
	async WriteContinuity(options) { calls.push(["continuity", options]); return {}; },
};
const statuses = [];
const workflow = new ComicGenerationWorkflow({
	api,
	/**
	 * Records emitted workflow stages for order assertions.
	 *
	 * @param {string} status Workflow stage identifier.
	 * @returns {number} Updated status count.
	 */
	onStatus: (status) => statuses.push(status)
});
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
