<?php

/**
 * Provides functionality for generating AI prompts for the Zeta Comic Generator.
 * 
 * Prompts are stored in the `$prompts` property of the class as HereDoc strings 
 * for easy editing:
 * - `$prompts->script`: A heredoc string containing the instructions for generating the comic strip script.
 * - `$prompts->background`: A heredoc string containing the instructions for generating the background descriptions.
 * - `$prompts->action`: A heredoc string containing the instructions for generating the action descriptions.
 */
class Prompts {

	function __construct() {
		$this->prompts = new stdClass;
		$this->prompts->system = <<<SYSTEM
		You are a cartoonist and humorist. 

		You write comic strips starring a zeta reticulan alien named Alpha Zeta. 

		Your comic strips follow a three act story structure. 
		The first panel creates a context for the story. 
		The middle panel creates conflict or a goal to achieve. 
		The last panel creates a resolution to the conflict or goal.

		Your comic strips are generally positive and funny but can be serious when called for.

		In your comic strips Alpha Zeta exists with, and interacts with, other characters and props in the scene. Alpha never comes into direct physical contact with characters or props in the scene. Alpha speaks to and references other characters in the scene, but those characters never respond verbally.

		In each panel of your comic strips, Alpha Zeta performs only one of the following actions: {p0}

		Alpha Zeta is about 4 feet tall and lanky in build. Alpha has green skin, large black eyes and a large bald head. Alpha has two arms, each with three fingers and a thumb. Alpha has two legs and feet. The feet do not have any digits. Alpha generally does not wear clothing, but will occasionally wear a costume when specified in the list of actions.

		{p1}

		Additional information about past events that can be drawn from in writing stories. Only include this information if relevent to the story you are writing.

		{p2}
		SYSTEM;

		$this->prompts->concept = <<<CONCEPT
		Write a concept for a three panel comic strip. In this comic strip, Alpha Zeta will engage in the following premise: {p0}
		Your concept will be written as a single paragraph. 
		Your concept will include a description of the overall story. 
		Your concept will include a breakdown of each panel. 
		Your concept will be no more than 100 words in length.

		Output your response as a valid json object in the following format:

		{
			\"concept\": \"\",
			\"memory\": [
				{ "id": 0, "description": "" },
			]
		}
		
		The value of the property \"memory\" is an array of objects. This array will contain a list of items used from the character profile or past events listed above in writing the story concept.
		Each object in the array contains the following properties:
		\"id\": The number prefixing the item from the lists.
		\"description\": The item from the lists.
		CONCEPT;

		//The following is a list of story elements that have been used in previous comics. These are called \"elements of significance\". Each element of significance is preceded by an identifying number. You may use these elements of significance in writing the comic strip. Using an element of significance is not required. Only use an element of significance if it is pertinent to the comic story.
		$this->prompts->script = <<<SCRIPT
		Write a script for the three panel comic strip as described in your concept.
		For each panel, include a detailed description of the scene and words spoken by the main character.

		Output your script as a valid json object in the following format:
		{
			\"title\": \"\",
			\"panels\": [
				{
					\"scene\": \"\",
					\"action\": \"\",
					\"dialog\": \"\"
				},
				{
					\"scene\": \"\",
					\"action\": \"\",
					\"dialog\": \"\"
				},
				{
					\"scene\": \"\",
					\"action\": \"\",
					\"dialog\": \"\"
				}
			]
		}

		The following is a description of each property value in the json object:
		`title`: The title of the comic strip. Limit to 50 letters.
		`panels` is a 3 element array of objects defining each of the 3 panels in the comic strip.
		`scene`: A description of the panel scene, including all characters present.
		`dialog`: Words spoken by Alpha Zeta. He is the only character that speaks. Do not label the dialog with a character name. This can be an empty string if the character is not speaking.
		`action`: A word, chosen from the following list, that best describes the action or appearance of Alpha Zeta in the panel: {p0}
		SCRIPT;

		$this->prompts->background = <<<BACKGROUND
		You will direct a team of artists who draw the art for your comic strip. You will write a separate visual description of each individual panel in your comic strip script. 

		Your description of each panel will not reference any element of the other panels. Any description of a character, prop or environment that persists across panels must be repeated in each description. Ensure that your descriptions are detailed enough to maintain visual consistency across all panels of the comic strip.

		Omit all references to Alpha Zeta in your description. 

		Omit any description of Alpha Zeta from your description.

		Do not reference a comic strip “panel”, or any other comic strip formatting in your description.
		Include enough detail necessary for an AI image generator to render an image of your description.

		Output your response as a valid json object in the following format:

		{
			\"descriptions\": []
		}

		The property “descriptions” is an array containing 3 elements. Each element is a string value containing your description of the panel. Do not exceed 1000 characters for each description.
		BACKGROUND;

		// $this->prompts->action = <<<ACTION
		// You are a talented artist who directs animated cartoons.
		// The following describes three scenes in a cartoon featuring the character Alpha Zeta:
		// - {p0}
		// - {p1}
		// - {p2}
		// For each of the three scenes choose one word, from the following list, which best describes the action or appearance of the main character:  {p3}.
		// Output your response as a valid json object in the follwing format:
		// {
		// 	\"panels\": [
		// 		\"word1\",
		// 		\"word2\",
		// 		\"word3\"
		// 	]
		// }
		// ACTION;

		$this->prompts->image = <<<IMAGE
		In the style of an animated cartoon, draw the following: {p0}
		Limit the use of the color green in your drawing to no more than 33 percent of the total pixels.
		IMAGE;

		$this->prompts->continuity = <<<CONTINUITY
		You are an experienced psychologist. You are also an experienced reporter who chronicles world events.
		
		Using the script for the story, identify the following:
		
		Identify any character traits that are portryed in Alpha Zeta.
		- Do not include anything listed in the character profile outlined at the beginning of this conversation.
		- It is okay if no new traits are portreyed.
		- Limit to no more than 3 traits. If there are more than 3, list the most prominant.

		Itentify any significant events in the life of Alpha Zeta portreyed in the story.
		- Do not include anything listed in the historical events outlined at the beginning of this conversation.
		- It is okay if no new events are portreyed.
		- Limit to no more than 3 events. If there are more than 3, list the most prominant.

		Output your response in the following format:
		{
			\"alpha\": []
			\"event\": []
		}
		CONTINUITY;
	}

	/**
	 * Generates a prompt based on the provided action and values.
	 *
	 * @param string $action The action for which to generate the prompt.
	 * @param array $values The values to replace placeholders in the prompt.
	 * @param array $system The system values to replace placeholders in the prompt.
	 * @return string The generated prompt, or an empty string if no prompt is defined for the given action.
	 */
	function generatePrompt($action, $values = array(), $rawOutput = false) {

		if(!isset($this->prompts->$action)) {
			return;
		}

		$prompt = "";
		
		if($rawOutput == true) {
			$prompt = $this->prompts->$action;
		} else {
			$instructions = $this->arrayFromHeredoc($this->prompts->$action);
			// Loop through each instruction
			foreach ($instructions as $instruction) {
				$prompt = $this->writePromptLine($prompt, $instruction);
			}
		}

		// Loop through each value in the values array
		foreach ($values as $index => $value) {
			// Replace placeholders like {p0}, {p1}, etc., with corresponding values
			$prompt = str_replace("{p{$index}}", $value, $prompt);
		}

		return $prompt;
	}

	/**
	 * Adds a new line to the prompt, adding a new line character if the prompt is not empty.
	 *
	 * @param string $prompt The existing prompt text.
	 * @param string $line The new line to append to the prompt.
	 * @return string The updated prompt with the new line appended.
	 */
	function writePromptLine($prompt, $line) {
		if($line == "") return $prompt;
		$newLine = "";
		if($prompt != "") $newLine = "\\n";

		$newLine .= $line;

		return $prompt . $newLine;
	}

	/**
	 * Converts a heredoc string into an array of lines.
	 *
	 * @param string $heredoc The heredoc string to convert.
	 * @return string[] An array of the lines in the heredoc string, with carriage return and newline characters removed.
	 */
	function arrayFromHeredoc($heredoc) {
		$result = explode("\n", $heredoc);
		foreach ($result as $key => $line) {
			// Remove carriage return and new line characters
			$result[$key] = str_replace(["\r", "\n"], '', $line);
		}
		return $result;
	}
}
?>