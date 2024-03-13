export default class CanvasComic {
	constructor(data) {
		console.log("data", data);
		if(!data || !data.script || !data.container) {
			console.error("Invalid data");
			return;
		}
		const {container, script} = data;

		container.innerHTML = `ready...`;
	}
}