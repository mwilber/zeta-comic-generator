<?php
$prompts = new stdClass;

$scriptPrompt = <<<SCRIPT
You are a cartoonist and humorist. Write the script for a three panel comic strip.
In the comic strip our main character, a short green humaniod alien named Alpha Zeta, engages in the following premise: {p0}
Include a detailed scene description and words spoken by the main character.
Write your script in the form of a json object in the following format:
{
    \"title\": \"\",
    \"panels\": [
        {
            \"scene\": \"\",
            \"description\": \"\"
        },
        {
            \"scene\": \"\",
            \"description\": \"\"
        },
        {
            \"scene\": \"\",
            \"description\": \"\"
        }
    ]
}

The following is a description of each property value for the json object:
`title`: The title of the comic strip. Limit to 50 letters.
`panels` is a 3 element array of objects defining each of the 3 panels in the comic strip.
`scene`: A description of the panel scene, including all characters present.
`dialog`: Words spoken by Alpha Zeta. He is the only character that speaks. Do not label the dialog with a character name. This can be an empty string if the character is not speaking.
SCRIPT;

$prompts->script = arrayFromHeredoc($scriptPrompt);

$prompts->background = array(
    "You are a talented artist who draws background art for animated cartoons. ",
    "Write Dall-E prompts to draw backgrounds for three animation cells. These animation cells depict our main character, Alpha Zeta, in a scene.",
    "Descriptions of the three scenes are as follows:",
    "- {p0}",
    "- {p1}",
    "- {p2}",
    " ",
    "Your Dall-E prompts will be written within the following rules: ",
    "- Describe each scene as it would look if the main character, Alpha Zeta, is not present.",
    "- No characters will speak to each other.",
    "- Do not include any items that contain readable text.",
    "- Do not reference a comic strip panel.",
    "Write the prompts as a json object with a single property `descriptions`, which is an array of strings containing each of the prompts."
);

$prompts->action = array(
    "The following statements describe a three part story.",
    "- {p0}",
    "- {p1}",
    "- {p2}",
    "For each of the three parts coose one word from the following which most closely describes the action of the main character: ",
    "{p3}.",
    "Write your response as a valid json object with a single property `panels`, which is an array of strings containing each of the chosen words."
);

function generatePrompt($instructions, $values) {

    $prompt = "";

    // Loop through each instruction
    foreach ($instructions as $instruction) {
        // Loop through each value in the values array
        foreach ($values as $index => $value) {
            // Replace placeholders like {p0}, {p1}, etc., with corresponding values
            $prompt = $prompt = writePromptLine($prompt, str_replace("{p{$index}}", $value, $instruction));
        }
    }

    return $prompt;
}

function writePromptLine($prompt, $line) {
    if($line == "") return $prompt;
    $newLine = "";
    if($prompt != "") $newLine = "\\n";

    $newLine .= $line;

    return $prompt . $newLine;
}

function arrayFromHeredoc($heredoc) {
    $result = explode("\n", $heredoc);
    foreach ($result as $key => $line) {
        // Remove carriage return and new line characters
        $result[$key] = str_replace(["\r", "\n"], '', $line);
    }
    return $result;
}
?>