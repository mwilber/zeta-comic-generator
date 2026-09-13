import { GenerationProgressDialog } from "../scripts/modules/GenerationProgressDialog.js";

/** Adapts the shared dialog to workflow events and the embedded frame. */
export class McpGenerationProgress {
	/**
	 * Connects the shared progress dialog to the MCP strip and status elements.
	 *
	 */
	constructor() {
		this.dialog = new GenerationProgressDialog(document.getElementById("statusdialog"));
		this.strip = document.getElementById("strip");
		this.summary = document.getElementById("app-status");
	}

	/**
	 * Shows initial progress and makes the strip inactive while generation runs.
	 *
	 * @returns {void}
	 */
	Start() {
		document.body.classList.add("is-generating");
		this.strip.inert = true;
		this.strip.setAttribute("aria-busy", "true");
		this.summary.classList.remove("visually-hidden");
		this.summary.classList.remove("is-error");
		this.summary.textContent = "\u00a0";
		this.dialog.Update(0);
		this.dialog.Show();
	}

	/**
	 * Displays the message for a recognized generation stage.
	 *
	 * @param {string} stage Workflow stage identifier; unknown stages are ignored.
	 * @returns {void}
	 */
	Stage(stage) {
		this.dialog.Stage(stage);
	}

	/**
	 * Passes the current completion percentage to the shared progress control.
	 *
	 * @param {number} amount Completion percentage, clamped by the shared control.
	 * @returns {void}
	 */
	Update(amount) {
		this.dialog.Update(amount);
	}

	/**
	 * Dismisses progress, restores strip interaction, and updates the persistent status.
	 *
	 * @param {string} message Text to display, or an empty string for a blank status.
	 * @param {boolean} isError Whether the status represents an error.
	 * @returns {void}
	 */
	Finish(message, isError = Boolean(message)) {
		const hadFocus = this.dialog.el.contains(document.activeElement);
		this.dialog.Hide();
		document.body.classList.remove("is-generating");
		this.strip.inert = false;
		this.strip.setAttribute("aria-busy", "false");
		this.strip.setAttribute("aria-label", "Comic strip");
		this.summary.textContent = message || "\u00a0";
		if (isError) this.summary.classList.add("is-error");
		else this.summary.classList.remove("is-error");
		if (hadFocus) this.strip.focus();
	}
}
