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
        You are a cartoonist and humorist. You are writing a comic strip starring a green zeta reticulan alien named Alpha Zeta.
        Your comic strip will generally be positive and funny but can be serious when called for.
        {p0}
        In each panel of your comic strip, Alpha Zeta will be able to perform only one of the following actions: {p1}
        SYSTEM;
        $this->prompts->concept = <<<CONCEPT
        Write a concept for a three panel comic. In this comic strip, Alpha Zeta will engage in the following premise: {p0}
        Your concept should be written as a single paragraph. Your concept should include description of the overall story. 
        Your concept should include a breakdown of each panel. You concept should be no more than 100 words in length.
        Output your response as a valid json object in the follwing format:
        {
            \"concept\": \"\",
        }
        CONCEPT;
        //The following is a list of story elements that have been used in previous comics. These are called \"elements of significance\". Each element of significance is preceded by an identifying number. You may use these elements of significance in writing the comic strip. Using an element of significance is not required. Only use an element of significance if it is pertinent to the comic story.
		$this->prompts->script = <<<SCRIPT
        Write the script for a three panel comic strip.
        Include a detailed scene description and words spoken by the main character.

        Output your response as a valid json object in the follwing format:
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
            ],
            \"memory\" : [],
            \"newmemory\" : []
        }

        The following is a description of each property value for the json object:
        `title`: The title of the comic strip. Limit to 50 letters.
        `panels` is a 3 element array of objects defining each of the 3 panels in the comic strip.
        `scene`: A description of the panel scene, including all characters present.
        `dialog`: Words spoken by Alpha Zeta. He is the only character that speaks. Do not label the dialog with a character name. This can be an empty string if the character is not speaking.
        `action`: A word, chosen from the list above, describing the action Alpha Zeta is performing in the panel.
        `memory`: An array of elements of significance, from the list above, used in the comic. The array will contain only the identifying number for the element of significance.
        `newmemory`: An array of identified new elements of significance. Output each new element of significance as an object with properties `type`, which is the number of classification from the list above, and `description`, a short description of the element of significance no more than 5 words.
        SCRIPT;

		$this->prompts->background = <<<BACKGROUND
        You are a talented artist who draws background art for animated cartoons.
        Given the provided script for the comic strip, for each panel, write a description of the background behind Alpha Zeta.
        Include enough detail necessary for an AI image generator to render an image of your description.
        Output your response as a valid json object in the follwing format:
        {
            \"descriptions\": []
        }
        Your descriptions will be written within the following rules:
        - `descriuptions` is an array of strings with 3 elements. Each element is a description of a scene in the comic strip.
        - Do not exceed 500 characters for each description.
        - Describe each scene as it would look if the main character, Alpha Zeta, is not present.
        - No characters will speak to each other.
        - Do not include any items that contain readable text.
        - Do not reference a comic strip panel.
        BACKGROUND;

		$this->prompts->action = <<<ACTION
        You are a talented artist who directs animated cartoons.
        The following describes three scenes in a cartoon featuring the character Alpha Zeta:
        - {p0}
        - {p1}
        - {p2}
        For each of the three scenes choose one word, from the following list, which best describes the action or appearance of the main character:  {p3}.
        Output your response as a valid json object in the follwing format:
        {
            \"panels\": [
                \"word1\",
                \"word2\",
                \"word3\"
            ]
        }
        ACTION;
		$this->prompts->image = <<<IMAGE
        In the style of an animated cartoon, draw the following: {p0}
        IMAGE;
	}

	/**
	 * Generates a prompt based on the provided action and values.
	 *
	 * @param string $action The action for which to generate the prompt.
	 * @param array $values The values to replace placeholders in the prompt.
     * @param array $system The system values to replace placeholders in the prompt.
	 * @return string The generated prompt, or an empty string if no prompt is defined for the given action.
	 */
	function generatePrompt($action, $values = array(), $system = array()) {

		if(!isset($this->prompts->$action)) {
			return;
		}

		$instructions = $this->arrayFromHeredoc($this->prompts->$action);
		$prompt = "";

		// Loop through each instruction
		foreach ($instructions as $instruction) {
			// Loop through each value in the values array
			foreach ($values as $index => $value) {
				// Replace placeholders like {p0}, {p1}, etc., with corresponding values
				$instruction = str_replace("{p{$index}}", $value, $instruction);
			}
			foreach ($system as $index => $value) {
				// Replace placeholders like {s0}, {s1}, etc., with corresponding values
				$instruction = str_replace("{s{$index}}", $value, $instruction);
			}
			$prompt = $this->writePromptLine($prompt, $instruction);
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