export class ComicGenerator {
	constructor() {
		this.defaultTextModel = 'oai';
		//TODO: add a `comic` property that is the complete current state of the comic
		//TODO: each `WriteXxx` method will update the `comic` property
		//TODO: pass updateHandler, a callback function that is called whenever the script is updated. The update handler will be a function that loads the script in the comic/script renderers.
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
		return result.json;
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