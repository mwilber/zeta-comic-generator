<?php
/**
 * Checks the premise for vulgarity using the Perspective API.
 *
 * Expects the POST parameter `premise` to be set.
 * This script assumes $output is initialized in the calling script (e.g., index.php).
 *
 * @example
    // Response JSON
    {
        "error": "",
        "json": {
            "vulgarity": false
        },
        "model": "perspective_api"
    }
 */

// Try to include the key file. GOOGLE_KEY is expected to be defined here.
if (file_exists(__DIR__ . '/../includes/key.php')) {
    include_once __DIR__ . '/../includes/key.php';
}

// Define a placeholder for the Google API key if not already defined
// This is mainly for isolated testing. In production, GOOGLE_KEY must be in key.php.
if (!defined('GOOGLE_KEY')) {
    define('GOOGLE_KEY', 'TEST_GOOGLE_KEY'); // Dummy key for subtask execution/testing
}

define('OBSCENE_THRESHOLD', 0.5);

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

// Ensure $output->json is initialized if this script is ever run standalone
// (though it's designed to be included from index.php where $output is set up)
if (!isset($output)) {
    // This case should ideally not be hit if used as intended.
    // If $output is not set by index.php, this script won't output correctly.
    // For robustness in case of direct call for testing, we might init it,
    // but the primary design is $output comes from index.php.
    // For now, we'll assume $output is always pre-initialized.
    // If direct calls become a use case, this needs proper handling for $output.
}
if (!isset($output->json)) {
    $output->json = new stdClass();
}


$output->model = "perspective_api"; // Set model early

// Get the premise from the POST request
$premise = isset($_POST['premise']) ? $_POST['premise'] : '';

if (empty($premise)) {
    $output->error = "Premise input is empty.";
    $output->json->vulgarity = null;
}
// Check if GOOGLE_KEY is defined and not empty.
// Allow 'TEST_GOOGLE_KEY' to proceed for testing the API call path.
else if (!defined('GOOGLE_KEY') || GOOGLE_KEY === '') {
    $output->error = "Google API key (GOOGLE_KEY) for Perspective API is not configured or is empty. Please check includes/key.php.";
    $output->json->vulgarity = null;
}
// If key is the specific test key, it's for testing the call path, but it's not a *valid* production key.
// The API call will proceed and likely fail at the API endpoint, which is expected for 'TEST_GOOGLE_KEY'.
else if (GOOGLE_KEY === 'TEST_GOOGLE_KEY') {
    // Intentionally proceed to callPerspectiveAPI with the test key
    $apiResponse = callPerspectiveAPI($premise, GOOGLE_KEY);

    if ($apiResponse === false) {
        $output->error = "Error calling moderation API (using TEST_GOOGLE_KEY).";
        $output->json->vulgarity = null;
    } elseif (isset($apiResponse->attributeScores->OBSCENE->summaryScore->value)) {
        $output->error = ""; // Clear error
        $obsceneScore = $apiResponse->attributeScores->OBSCENE->summaryScore->value;
        $output->json->vulgarity = $obsceneScore > OBSCENE_THRESHOLD;
    } else {
        $output->error = "Invalid response from moderation API (using TEST_GOOGLE_KEY).";
        if (isset($apiResponse->error->message)) {
             $output->error .= " - API Message: " . $apiResponse->error->message;
        }
        $output->json->vulgarity = null;
    }
}
// This is the normal operational path if GOOGLE_KEY is defined, not empty, and not the test key.
else {
    $apiResponse = callPerspectiveAPI($premise, GOOGLE_KEY);

    if ($apiResponse === false) {
        $output->error = "Error calling moderation API.";
        $output->json->vulgarity = null;
    } elseif (isset($apiResponse->attributeScores->OBSCENE->summaryScore->value)) {
        $output->error = ""; // Clear error if API call was successful and response is valid
        $obsceneScore = $apiResponse->attributeScores->OBSCENE->summaryScore->value;
        $output->json->vulgarity = $obsceneScore > OBSCENE_THRESHOLD;
    } else {
        $output->error = "Invalid response from moderation API.";
         if (isset($apiResponse->error->message)) {
             $output->error .= " - API Message: " . $apiResponse->error->message;
         }
        $output->json->vulgarity = null;
    }
}

// The calling script (index.php) is responsible for:
// header('Content-Type: application/json');
// echo json_encode($output);

?>
