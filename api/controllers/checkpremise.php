<?php
/**
 * Checks the premise for vulgarity using the Perspective API.
 *
 * Expects the POST parameter `premise` to be set.
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

// Try to include the key file, otherwise define a dummy key for testing
if (file_exists(__DIR__ . '/../includes/key.php')) {
    include_once __DIR__ . '/../includes/key.php';
}

// Define a placeholder for the Perspective API key if not already defined
if (!defined('PERSPECTIVE_API_KEY')) {
    define('PERSPECTIVE_API_KEY', 'TEST_API_KEY'); // Dummy key for subtask execution
}

define('OBSCENE_THRESHOLD', 0.5);

/**
 * Calls the Perspective API to analyze text.
 *
 * @param string $text The text to analyze.
 * @param string $apiKey The API key for the Perspective API.
 * @return object|false The decoded JSON response from the API, or false on error.
 */
function callPerspectiveAPI($text, $apiKey) {
    $url = "https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze?key=" . $apiKey;

    $data = [
        "comment" => ["text" => $text],
        // "languages" => ["en"], // Omitted for auto-detection as per requirement
        "requestedAttributes" => [
            "OBSCENE" => new stdClass(), // Using stdClass for empty JSON object {}
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
        // Optionally log curl_error($ch)
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    $decodedResponse = json_decode($response);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // Optionally log json_last_error_msg() and the raw $response
        return false;
    }

    return $decodedResponse;
}

// Initialize the output object
$output = new stdClass();
$output->json = new stdClass();
$output->model = "perspective_api"; // Set model early

// Get the premise from the POST request
$premise = isset($_POST['premise']) ? $_POST['premise'] : '';

if (empty($premise)) {
    $output->error = "Premise input is empty.";
    $output->json->vulgarity = null;
} elseif (!defined('PERSPECTIVE_API_KEY') || PERSPECTIVE_API_KEY === 'YOUR_PERSPECTIVE_API_KEY_HERE' || PERSPECTIVE_API_KEY === 'TEST_API_KEY' || PERSPECTIVE_API_KEY === '') {
    // Added check for the default placeholder value or an empty string
    // In a real scenario, 'TEST_API_KEY' would also be an invalid key for actual API calls.
    // For this subtask, we proceed if it's TEST_API_KEY to allow testing the call structure.
    // However, if it's the initial placeholder or empty, it's an error.
    if (PERSPECTIVE_API_KEY === 'YOUR_PERSPECTIVE_API_KEY_HERE' || PERSPECTIVE_API_KEY === '') {
        $output->error = "API key not configured.";
        $output->json->vulgarity = null;
    } else {
        // Proceed if key is 'TEST_API_KEY' for testing the call structure,
        // knowing it will likely fail with the actual API but allows testing the path.
        $apiResponse = callPerspectiveAPI($premise, PERSPECTIVE_API_KEY);

        if ($apiResponse === false) {
            $output->error = "Error calling moderation API.";
            $output->json->vulgarity = null;
        } elseif (isset($apiResponse->attributeScores->OBSCENE->summaryScore->value)) {
            $output->error = "";
            $obsceneScore = $apiResponse->attributeScores->OBSCENE->summaryScore->value;
            $output->json->vulgarity = $obsceneScore > OBSCENE_THRESHOLD;
            // You could also store the score if needed:
            // $output->json->scores = $apiResponse->attributeScores;
        } else {
            // Log the actual response for debugging if possible
            // error_log("Invalid response from moderation API: " . json_encode($apiResponse));
            $output->error = "Invalid response from moderation API.";
            $output->json->vulgarity = null;
            if (isset($apiResponse->error->message)) {
                 $output->error .= " - API Message: " . $apiResponse->error->message;
            }
        }
    }
} else {
    $apiResponse = callPerspectiveAPI($premise, PERSPECTIVE_API_KEY);

    if ($apiResponse === false) {
        $output->error = "Error calling moderation API.";
        $output->json->vulgarity = null;
    } elseif (isset($apiResponse->attributeScores->OBSCENE->summaryScore->value)) {
        $output->error = ""; // Clear error if API call was successful and response is valid
        $obsceneScore = $apiResponse->attributeScores->OBSCENE->summaryScore->value;
        $output->json->vulgarity = $obsceneScore > OBSCENE_THRESHOLD;
        // You could also store the full scores if needed for debugging or more complex logic:
        // $output->json->scores = $apiResponse->attributeScores;
    } else {
        // Log the actual response for debugging if possible
        // error_log("Invalid response from moderation API: " . json_encode($apiResponse));
        $output->error = "Invalid response from moderation API.";
        $output->json->vulgarity = null;
         if (isset($apiResponse->error->message)) {
             $output->error .= " - API Message: " . $apiResponse->error->message;
         }
    }
}


// Set the content type to application/json
header('Content-Type: application/json');

// Output the JSON response
echo json_encode($output);

?>
