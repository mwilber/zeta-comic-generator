/** Shared progress controls for the website and the embedded MCP app. */
export class GenerationProgressDialog {
	constructor(el) {
		this.el = el;
		this.status = el.querySelector("#status");
		this.progress = el.querySelector("progress");
	}

	Show(message = "generating") {
		this.SetMessage(message);
		this.el.classList.add("active");
		this.el.setAttribute("aria-hidden", "false");
		this.el.focus();
	}

	Hide() {
		this.el.classList.remove("active");
		this.el.setAttribute("aria-hidden", "true");
	}

	SetMessage(message) {
		this.status.textContent = message;
	}

	Update(amount) {
		amount = Number.isFinite(amount) ? Math.max(0, Math.min(100, amount)) : 0;
		this.progress.setAttribute("value", amount);
		this.progress.textContent = amount + "%";
		this.progress.setAttribute("aria-valuetext", `${amount}% complete.`);
	}
}
