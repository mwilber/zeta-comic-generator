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
$output->json = new stdClass();

// Be lenient and allow the premise to be used if the API call fails.
$output->json->reject = false;

if (empty($premise)) {
    $output->error = "Premise input is empty.";
} else {
    $apiResponse = callPerspectiveAPI($premise, PERSPECTIVE_KEY);

    if ($apiResponse && isset($apiResponse->attributeScores->OBSCENE->summaryScore->value)) {
        $obsceneScore = $apiResponse->attributeScores->OBSCENE->summaryScore->value;
        $toxicityScore = $apiResponse->attributeScores->TOXICITY->summaryScore->value;
        $severeToxicityScore = $apiResponse->attributeScores->SEVERE_TOXICITY->summaryScore->value;

        $output->json->score = $obsceneScore;
        $output->json->toxicity = $toxicityScore;
        $output->json->severeToxicity = $severeToxicityScore;
        $output->json->reject = ($obsceneScore > OBSCENE_THRESHOLD || $toxicityScore > OBSCENE_THRESHOLD || $severeToxicityScore > OBSCENE_THRESHOLD);
    } else {
        if (isset($apiResponse->error->message)) {
             $output->json->error .= "API Message: " . $apiResponse->error->message;
        } else {
            $output->json->error = "Error calling moderation API.";
        }
    }
}
?>
