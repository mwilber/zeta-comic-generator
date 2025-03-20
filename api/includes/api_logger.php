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
     * @param array $payload The payload sent to the API
     * @param array $headers The request headers
     * @param string $body The request body
     * @param string $response The raw response from the API
     * @param stdClass $result The processed result
     */
    public static function logRequest($id, $action, $title, $payload, $body, $response, $result) {
        $database = new Database();
		$db = $database->getConnection();
        
        // prepare query statement
        $stmt = $db->prepare("INSERT INTO `requestlog` 
            (`workflowId`, `action`, `title`, `payload`, `body`, `response`, `result`) 
            VALUES (
                ".$db->quote($id).",
                ".$db->quote($action).",
                ".$db->quote($title).",
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