/**
 * @file CharacterAction.js
 * @author Matthew Wilber
 * @license GPL-3.0
 * @version 1.0.0
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
		angry: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 260,
				y: 120
			}
		},
		approval: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 305,
				y: 205
			}
		},
		creeping: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 290,
				y: 175
			}
		},
		disguised: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 230,
				y: 125
			}
		},
		enamored: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 300,
				y: 140
			}
		},
		explaining: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 280,
				y: 130
			}
		},
		joyous: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 280,
				y: 70
			}
		},
		running: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 280,
				y: 165
			}
		},
		santa_claus_costume: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 240,
				y: 215
			}
		},
		selfie: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 290,
				y: 110
			}
		},
		sitting: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 190,
				y: 190
			}
		},
		standing: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 310,
				y: 140
			}
		},
		startled: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 190,
				y: 150
			}
		},
		teaching: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 360,
				y: 220
			}
		},
		terrified: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 265,
				y: 105
			}
		},
		typing: {
			center: {
				x: 256,
				y: 10
			},
			pointer: {
				x: 335,
				y: 235
			}
		}
	};

	/**
	 * Retrieves the image data for a character action.
	 *
	 * @param {string} action - The action to retrieve the image data for.
	 * @param {object} character - The character object associated with the action.
	 * @returns {object} The image data, including the URL, CSS class, and dialog balloon location information.
	 */
	static GetActionImageData(action, character) {
		let imageData = {};

		action = action.toLowerCase();
		action = this._balloonLocations[action] ? action : "standing";

		imageData.url = "/assets/character_art/" + action + ".png";
		imageData.className = "character";
		imageData.balloon = {
			character,
			type: "speech",
			location: this._balloonLocations[action]
		};

		return imageData;
	}
}