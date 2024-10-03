document.addEventListener("DOMContentLoaded", () => {
	AttachUiEvents();

// 	setTimeout(() => {
// 		const dialog = document.getElementById("alertdialog");
// 		dialog.classList[
// 			dialog.classList.contains("active") ? "remove" : "add"
// 		]("active");
// 		dialog.setAttribute("aria-hidden", "false");
// 		// Focus the first child of dialog element
// 		const closeBtn = dialog.querySelector(".close");
// 		if (closeBtn) {
// 			closeBtn.focus();
// 		}
// 	}, 1000);
// 	

});

function AttachUiEvents() {
	const UIevents = [
		{
			selector: ".dialog .close",
			event: "click",
			handler: (e) => {
				e.target.parentElement.parentElement.classList.remove("active");
				e.target.parentElement.parentElement.setAttribute("aria-hidden", "true");
				document.querySelector("#query").focus();
			},
		},
	];

	for (const event of UIevents) {
		document.querySelectorAll(event.selector).forEach((el) => {
			el.addEventListener(event.event, event.handler);
		});
	}
}