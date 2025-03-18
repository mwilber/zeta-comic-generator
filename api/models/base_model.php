<?php
/**
 * Base class for AI model interactions. Based on the OpenAI API.
 */
class BaseModel {
	public $modelName;
	public $apiUrl;
	protected $apiKey;
	protected $responseFormat = "json_object";

	/**
	 * Send a payload to the model and return the result.
	 * The payload is the messages array in the format of the OpenAI API.
	 * 
	 * @param array $payload The payload to send to the model.
	 * @return stdClass The result of the model's response.
	 */
	function sendPayload($payload) {
		$payload = $this->processPayload($payload);
		$headers = $this->buildRequestHeaders();
		$body = $this->buildRequestBody($payload);
		$response = $this->sendRequest($headers, $body);
		$result = $this->processResponse($response);
		return $result;
	}

	/**
	 * **Overload this method to customize the request headers for the model.**
	 * 
	 * Build the request headers for CURL. Default is based on the OpenAI API.
	 * 
	 * @return array The request headers.
	 */
	protected function buildRequestHeaders() {
		return array(
			'Authorization: Bearer ' . $this->apiKey,
			'Content-Type: application/json',
		);
	}

	/**
	 * **Overload this method to customize the request body for the model.**
	 * 
	 * Build the request body for CURL. Default is based on the OpenAI API.
	 * 
	 * @param array $messages The messages array.
	 * @return string The request body.
	 */
	protected function buildRequestBody($messages) {
		$messagesArray = [];
		foreach ($messages as $message) {
			// Replace "system" role with "developer"
			if (isset($message->role) && $message->role === "system") {
				$message->role = "developer";
			}
			$messagesArray[] = [
				"role" => $message->role,
				"content" => $message->content
			];
		}
		$body = '{
			"model": "'.$this->modelName.'",
			"response_format": { "type": "'.$this->responseFormat.'" },
			"messages": ' . json_encode($messagesArray) . '
		}';

		return $body;
	}

	/**
	 * **Overload this method to customize the payload for the model.**
	 * 
	 * Process the payload before sending it to the model.
	 * 
	 * @param array $payload The payload to send to the model.
	 * @return array The processed payload.
	 */
	protected function processPayload($payload) {
		return $payload;
	}

	/**
	 * **Overload this method to customize the response from the model.**
	 * 
	 * Process the response from the model.
	 * 
	 * @param string $response The response from the model.
	 * @return stdClass The result of the model's response.
	 */
	protected function processResponse($response) {
		$result = new stdClass;
		$json = json_decode($response);
		$result->data = $json;

		$result->error = $json->error;

		if(isset($json->choices[0]->message->content)) {
			$script = trim($json->choices[0]->message->content);
			$script = str_replace("\\n", "", $script);
			$script = str_replace("\\r", "", $script);
			$script = str_replace("\\t", "", $script);
			$script = str_replace("```json", "", $script);
			$script = str_replace("`", "", $script);
			$jscript = json_decode($script);

			$result->debug = $script;
			if($jscript) $result->json = $jscript;
		}

		return $result;
	}

	/**
	 * Send a request to the model using CURL.
	 * 
	 * @param array $headers The request headers.
	 * @param string $body The request body.
	 * @return string The response from the model.
	 */
	protected function sendRequest($headers, $body) {
		$response = new stdClass;
		$response->data = null;
		$response->script = null;
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
		curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
		// Timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	
		$response = curl_exec($ch);

		return $response;
	}
}
?> 