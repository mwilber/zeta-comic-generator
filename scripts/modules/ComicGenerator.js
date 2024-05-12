export class ComicGenerator {
	constructor(params) {
		this.defaultTextModel = 'oai';
		this.comic = null;
		this.onUpdate = params.onUpdate || null;
		//TODO: Add a ResetComic method
	}

	async WriteScript(premise, params) {
		const { model } = params || {};
		let result = await this.fetchApi('script', {
			query: premise,	
			model: model || this.defaultTextModel,
		});

		//TODO: improve error handling, see comment at fetchApi
		if(!result || !result.json || !result.json.panels || !result.json.panels.length) 
			return {error: "Script object not returned."};
		
		//TODO: Update the dialog properties here with the new array format
		for(const panel of result.json.panels) {
			if(typeof panel.dialog === "string") {
				panel.dialog = [{
					character: "alpha",
					text: panel.dialog,
				}];
			}
		}
		this.comic = result.json;

		this.onUpdate(this.comic, this.PercentComplete());
		return this.comic;
	}

	async WriteBackground(params) {
		const { model } = params || {};
		if (this.PercentComplete < 10) {
			console.log("ComicGenerator: Scene descriptions not written yet. Call WriteScript first.");
		}

		let fetchParams = {
			model: model || this.defaultTextModel,
		}

		for(const[idx, panel] of this.comic.panels.entries()) {
			fetchParams["panel" + (idx+1)] = panel.scene || "";
		}
		
		const result = await this.fetchApi('background', fetchParams);
		for(const[idx, background] of result.json.descriptions.entries()) {
			this.comic.panels[idx].background = background;
		}

		this.onUpdate(this.comic, this.PercentComplete());
		return this.comic;
	}

	PercentComplete() {
		let progress = 0;
		if(!this.comic || !this.comic.panels || !this.comic.panels.length) return progress;

		progress += 1;
		// Each panel has a total progress of 33
		for(const panel of this.comic.panels) {
			if(panel.scene) progress += 5;
			if(panel.images && panel.images.length) progress += 15;
			if(panel.dialog && panel.dialog.length) progress += 5;
			if(panel.background) progress += 5;
			if(panel.action) progress += 3;
		}

		return progress;
	}

	//TODO: Add retry functionality. Look for empty error and populated json
	// // Sometimes GPT returns a null, retry up to 2 times to get a usable result.
	// while(retry > 0) {
	// 	retry--;
	// 	let response = await queryApi('/api/' + endpoint + '/?c='+(Math.floor(Math.random()*1000)), sceneData);
	// 	if(response.json && response.json.panels && response.json.panels.length) {
	// 		result = [...response.json.panels];
	// 		model = response.model;
	// 		break;
	// 	} else if(response.json && response.json.descriptions && response.json.descriptions.length) {
	// 		result = [...response.json.descriptions];
	// 		model = response.model;
	// 		break;
	// 	}
	// }
	async fetchApi(action, data) {
		let uri = '/api/' + action + '/?c=' + (Math.floor(Math.random() * 100));

		const formData = new FormData();
		Object.keys(data).forEach((key) => {
			formData.append(key, data[key]);
		});

		try {
			const response = await fetch(uri, {
				method: 'POST',
				body: formData
			});
			const data = await response.json();
			console.log("ComicGenerator: API response", data);
			return data;
		} catch (error) {
			console.error('Error fetching API:', error);
			return false;
		}
	}
}