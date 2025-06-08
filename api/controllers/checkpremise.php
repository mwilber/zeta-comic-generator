<?php
/**
 * Checks the premise for vulgarity using the Perspective API.
 *
 * Expects the POST parameter `premise` to be set.
 * This script assumes $output is initialized in the calling script (e.g., index.php)
 * and that PERSPECTIVE_KEY is available/defined globally, typically via `includes/key.php`
 * being included in the main `index.php`.
 *
 * The script will set $output->error and $output->json.
 *
 * @example
    // Success Response JSON
    {
        "error": "",
        "vulgarity": {
            "score": 0.123,
            "reject": false
        }
    }
    // Error Response JSON (e.g., empty premise)
    {
        "error": "Premise input is empty.",
        "vulgarity": null
    }
 */

define('OBSCENE_THRESHOLD', 0.5); // Threshold for 'OBSCENE' attribute

/**
 * Calls the Perspective API to analyze text.
 *
 * @param string $text The text to analyze.
 * @param string $apiKey The API key for the Perspective API (should be PERSPECTIVE_KEY).
 * @return object|false The decoded JSON response from the API, or false on error.
 */
function callPerspectiveAPI($text, $apiKey) {
    $url = "https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze?key=" . $apiKey;

    $data = [
        "comment" => ["text" => $text],
        "requestedAttributes" => [
            "OBSCENE" => new stdClass(),
            "TOXICITY" => new stdClass(),
            "SEVERE_TOXICITY" => new stdClass()
        ]
    ];
    $jsonData = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("Curl error in callPerspectiveAPI: " . curl_error($ch));
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    $decodedResponse = json_decode($response);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in callPerspectiveAPI: " . json_last_error_msg() . ". Response: " . $response);
        return false;
    }

    return $decodedResponse;
}

// Get the premise from the POST request
$premise = isset($_POST['premise']) ? $_POST['premise'] : '';

if (empty($premise)) {
    $output->error = "Premise input is empty.";
    $output->json = null;
}
// Check if PERSPECTIVE_KEY is defined and not empty.
// The fallback 'TEST_PERSPECTIVE_KEY_ISOLATED' is allowed to proceed for isolated testing.
else if (!defined('PERSPECTIVE_KEY') || PERSPECTIVE_KEY === '') {
    // This case implies PERSPECTIVE_KEY was explicitly defined as empty, or was not defined at all AND
    // the fallback define('PERSPECTIVE_KEY', 'TEST_PERSPECTIVE_KEY_ISOLATED') was somehow skipped or PERSPECTIVE_KEY became undefined again.
    // Primarily, this catches an empty string from key.php.
    $output->error = "Google API key (PERSPECTIVE_KEY) for Perspective API is not configured (empty or not defined). Please check main configuration (includes/key.php).";
    $output->json = null;
}
// This is the normal operational path if PERSPECTIVE_KEY is defined, not empty, and not a test key.
else {
    $apiResponse = callPerspectiveAPI($premise, PERSPECTIVE_KEY);

    if ($apiResponse === false) {
        $output->error = "Error calling moderation API.";
        $output->json = null;
    } elseif (isset($apiResponse->attributeScores->OBSCENE->summaryScore->value)) {
        $output->error = ""; // Clear error if API call was successful and response is valid
        $obsceneScore = $apiResponse->attributeScores->OBSCENE->summaryScore->value;

        $output->json = new stdClass();
        $output->json->score = $obsceneScore;
        $output->json->reject = ($obsceneScore > OBSCENE_THRESHOLD);
    } else {
        $output->error = "Invalid response from moderation API.";
         if (isset($apiResponse->error->message)) {
             $output->error .= " - API Message: " . $apiResponse->error->message;
         }
        $output->json = null;
    }
}

?>
