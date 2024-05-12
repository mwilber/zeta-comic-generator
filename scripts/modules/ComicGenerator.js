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
		const result = await this.fetchApi('background', {
			model: model || this.defaultTextModel,
		});
	}

	PercentComplete() {
		let progress = 0;
		if(!this.comic || !this.comic.panels || !this.comic.panels.length) return progress;

		progress += 1;
		// Each panel has a total progress of 33
		for(const panel of this.comic.panels) {
			progress += 3;
			if(panel.images && panel.images.length) progress += 15;
			if(panel.dialog && panel.dialog.length) progress += 5;
			if(panel.background) progress += 5;
			if(panel.action) progress += 5;
		}

		return progress;
	}

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