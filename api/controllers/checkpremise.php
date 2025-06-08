<?php
/**
 * Checks the premise for vulgarity using the Perspective API.
 *
 * Expects the POST parameter `premise` to be set.
 * This script assumes $output is initialized in the calling script (e.g., index.php)
 * and that GOOGLE_KEY is available/defined globally, typically via `includes/key.php`
 * being included in the main `index.php`.
 *
 * The script will set $output->error and $output->vulgarity.
 *
 * @example
    // Success Response JSON
    {
        "error": "",
        // "model": "perspective_api", // This is no longer set by this controller
        "vulgarity": {
            "score": 0.123,
            "reject": false
        }
    }
    // Error Response JSON (e.g., empty premise)
    {
        "error": "Premise input is empty.",
        // "model": "perspective_api", // This is no longer set by this controller
        "vulgarity": null
    }
 */

// Define a placeholder for the Google API key if not already defined.
// This is mainly for isolated testing IF key.php was not included by index.php
// OR if GOOGLE_KEY was not in key.php.
// In a standard operational flow, GOOGLE_KEY should be defined by index.php including key.php.
if (!defined('GOOGLE_KEY')) {
    // This fallback makes the controller runnable in isolation for basic tests,
    // but it's not the primary way GOOGLE_KEY should be provided.
    define('GOOGLE_KEY', 'TEST_GOOGLE_KEY_ISOLATED');
}

define('OBSCENE_THRESHOLD', 0.5); // Threshold for 'OBSCENE' attribute

/**
 * Calls the Perspective API to analyze text.
 *
 * @param string $text The text to analyze.
 * @param string $apiKey The API key for the Perspective API (should be GOOGLE_KEY).
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
    $output->vulgarity = null;
}
// Check if GOOGLE_KEY is defined and not empty.
// The fallback 'TEST_GOOGLE_KEY_ISOLATED' is allowed to proceed for isolated testing.
else if (!defined('GOOGLE_KEY') || GOOGLE_KEY === '') {
    // This case implies GOOGLE_KEY was explicitly defined as empty, or was not defined at all AND
    // the fallback define('GOOGLE_KEY', 'TEST_GOOGLE_KEY_ISOLATED') was somehow skipped or GOOGLE_KEY became undefined again.
    // Primarily, this catches an empty string from key.php.
    $output->error = "Google API key (GOOGLE_KEY) for Perspective API is not configured (empty or not defined). Please check main configuration (includes/key.php).";
    $output->vulgarity = null;
}
// If key is the specific test key (either from this script's fallback or a test setup),
// it's for testing the call path, but it's not a *valid* production key.
// The API call will proceed and likely fail at the API endpoint, which is expected.
else if (GOOGLE_KEY === 'TEST_GOOGLE_KEY_ISOLATED' || GOOGLE_KEY === 'TEST_GOOGLE_KEY') { // Second condition for tests
    $apiResponse = callPerspectiveAPI($premise, GOOGLE_KEY);

    if ($apiResponse === false) {
        $output->error = "Error calling moderation API (using a test key: " . GOOGLE_KEY . ").";
        $output->vulgarity = null;
    } elseif (isset($apiResponse->attributeScores->OBSCENE->summaryScore->value)) {
        $output->error = ""; // Clear error
        $obsceneScore = $apiResponse->attributeScores->OBSCENE->summaryScore->value;

        $output->vulgarity = new stdClass();
        $output->vulgarity->score = $obsceneScore;
        $output->vulgarity->reject = ($obsceneScore > OBSCENE_THRESHOLD);
    } else {
        $output->error = "Invalid response from moderation API (using a test key: " . GOOGLE_KEY . ").";
        if (isset($apiResponse->error->message)) {
             $output->error .= " - API Message: " . $apiResponse->error->message;
        }
        $output->vulgarity = null;
    }
}
// This is the normal operational path if GOOGLE_KEY is defined, not empty, and not a test key.
else {
    $apiResponse = callPerspectiveAPI($premise, GOOGLE_KEY);

    if ($apiResponse === false) {
        $output->error = "Error calling moderation API.";
        $output->vulgarity = null;
    } elseif (isset($apiResponse->attributeScores->OBSCENE->summaryScore->value)) {
        $output->error = ""; // Clear error if API call was successful and response is valid
        $obsceneScore = $apiResponse->attributeScores->OBSCENE->summaryScore->value;

        $output->vulgarity = new stdClass();
        $output->vulgarity->score = $obsceneScore;
        $output->vulgarity->reject = ($obsceneScore > OBSCENE_THRESHOLD);
    } else {
        $output->error = "Invalid response from moderation API.";
         if (isset($apiResponse->error->message)) {
             $output->error .= " - API Message: " . $apiResponse->error->message;
         }
        $output->vulgarity = null;
    }
}

// The calling script (index.php) is responsible for:
// - Ensuring GOOGLE_KEY is defined (typically by including includes/key.php)
// - Initializing $output object
// - Setting header('Content-Type: application/json');
// - echo json_encode($output);

?>
