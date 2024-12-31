/**
 * @file CharacterAction.js
 * @author Matthew Wilber
 * @license GPL-3.0
 *
 * A static helper class with functions to generate character action metadata for Zeta Comic Generator.
 * Each action corresponds to a character image in the Zeta Comic Generator. For a list of action images,
 * go to https://comicgenerator.greenzeta.com/about.
 *
 * Provides image url and dialog balloon location coordinates for each action. Coordinates are necessary
 * to position the balloon at the character's position in the image.
 */
export class CharacterAction {
	static _balloonLocations = {
		analysis: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 190,
				y: 130,
			},
		},
		angry: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 260,
				y: 120,
			},
		},
		approval: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 305,
				y: 205,
			},
		},
		creeping: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 290,
				y: 175,
			},
		},
		disguised: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 230,
				y: 125,
			},
		},
		enamored: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 300,
				y: 140,
			},
		},
		explaining: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 280,
				y: 130,
			},
		},
		joyous: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 280,
				y: 70,
			},
		},
		laughing: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 190,
				y: 140,
			},
		},
		reporting: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 200,
				y: 160,
			},
		},
		running: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 280,
				y: 165,
			},
		},
		santa_claus_costume: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 240,
				y: 215,
			},
		},
		scifi_costume: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 310,
				y: 140,
			},
		},
		selfie: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 290,
				y: 110,
			},
		},
		sitting: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 190,
				y: 190,
			},
		},
		standing: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 310,
				y: 140,
			},
		},
		startled: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 190,
				y: 150,
			},
		},
		teaching: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 360,
				y: 220,
			},
		},
		terrified: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 265,
				y: 105,
			},
		},
		trick_or_treat: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 290,
				y: 150,
			},
		},
		typing: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 335,
				y: 235,
			},
		},
		writing: {
			center: {
				x: 256,
				y: 10,
			},
			pointer: {
				x: 200,
				y: 235,
			},
		},
	};

	/**
	 * Gets the data for the dialog balloon to be displayed for the given character action.
	 *
	 * @param {string} action - The action to get the dialog balloon data for. If the action is not found in the _balloonLocations object, the "standing" action is used.
	 * @param {object} character - The character object.
	 * @returns {object} The dialog balloon data for the specified action.
	 */
	static GetDialogBalloonData(action, character) {
		action = action.toLowerCase();
		action = this._balloonLocations[action] ? action : "standing";

		return this._balloonLocations[action];
	}

	static GetValidAction(action) {
		return this._balloonLocations[action] ? action : "standing";
	}

	/**
	 * Gets the URL for the image of a character action. This will return an empty string
	 * if no action is provided and defualt to "standing" if the action is not a valid value.
	 *
	 * @param {string} action - The name of the character action.
	 * @returns {string} The URL for the image of the character action.
	 */
	static GetImageUrl(action) {
		if (!action) return "";
		action = this._balloonLocations[action] ? action : "standing";
		return "/assets/character_art/" + action + ".png";
	}
}
