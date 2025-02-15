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

	/**
	 * Gets the data for the dialog balloon to be displayed for the given character action.
	 *
	 * @param {string} action - The action to get the dialog balloon data for. If the action is not found in the _balloonLocations object, the "standing" action is used.
	 * @param {object} character - The character object.
	 * @returns {object} The dialog balloon data for the specified action.
	 */
	static GetDialogBalloonData(action, character) {
		action = action.toLowerCase();
		action = characterActions[action] ? action : "standing";

		return {
			center: {
				x: 256,
				y: 10
			},
			...characterActions[action]
		};
	}

	static GetValidAction(action) {
		return characterActions[action] ? action : "standing";
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
		action = characterActions[action] ? action : "standing";
		return "/assets/character_art/" + action + ".png";
	}
}
