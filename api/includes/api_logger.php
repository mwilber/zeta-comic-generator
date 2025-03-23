<?php
/**
 * Static class for logging API requests and responses
 */
class ApiLogger {
    /**
     * Log an API request and its response to the database
     * 
     * @param string $id Unique identifier for the request
     * @param string $action Action type for the request
     * @param string $title Title for the request
     * @param string $model Model used for the request
     * @param array $payload The payload sent to the API
     * @param stdClass $body The request body
     * @param stdClass $response The raw response from the API
     * @param stdClass $result The processed result
     */
    public static function logRequest($id, $action, $title, $model, $payload, $body, $response, $result) {
        $database = new Database();
		$db = $database->getConnection();
        
        // prepare query statement
        // TODO: response and body are being stringified twice before being inserted into the database
        // this is happening in the openai calls. Not sure why it works. But aws is throwing it up.
        // Need to figure out the proper state. JSON objects should NOT be encapsulated in double quotes in the database.
        // IMPORTANT: USE THE TEXT REQUEST AND SWAP OUT THE MODELS RATHER THAN GENERATING COMICS
        $stmt = $db->prepare("INSERT INTO `requestlog` 
            (`workflowId`, `action`, `title`, `model`, `payload`, `body`, `response`, `result`) 
            VALUES (
                ".$db->quote($id).",
                ".$db->quote($action).",
                ".$db->quote($title).",
                ".$db->quote($model).",
                ".$db->quote(json_encode($payload)).",
                ".$db->quote(json_encode($body)).",
                ".$db->quote(json_encode($response)).",
                ".$db->quote(json_encode($result))."
            );");
        // execute query
        $stmt->execute();
    }
}
?> 