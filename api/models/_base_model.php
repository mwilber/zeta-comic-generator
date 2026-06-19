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
	 * @param string $id Unique identifier for the request.
	 * @param string $action action type for the request.
	 * @param string $title title for the request.
	 * @return stdClass The result of the model's response.
	 */
	function sendPayload($payload, $id, $action, $title) {
		$processedPayload = null;
		$headers = null;
		$body = null;
		$response = null;
		$result = null;

		try {
			$processedPayload = $this->processPayload($payload);
			$headers = $this->buildRequestHeaders();
			$body = $this->buildRequestBody($processedPayload);
			$response = $this->sendRequest($headers, $body);
			$result = $this->processResponse($response);
		} catch (Exception $e) {
			$result = new stdClass();
			$result->error = $e->getMessage();
		} finally {
			ApiLogger::logRequest(
				$id ?? 'unknown',
				$action ?? 'unknown', 
				$title ?? 'unknown',
				$this->modelName ?? 'unknown',
				$processedPayload ?? $payload ?? [],
				$body ?? '',
				$response ?? '',
				$result ?? new stdClass()
			);
		}

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
	 * @return stdClass The request body.
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

		return json_decode($body);
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

		// If $response is a string, extract the JSON object from it.
		if(is_string($response)) {
			$response = json_decode($response);
		}

		$result = new stdClass;
		$json = $response;
		$result->data = $json;

		$result->error = $json->error ?? null;

		$script = $this->extractTextFromResponse($json);

		if($script !== null) {
			$script = $this->cleanJsonText($script);
			$jscript = json_decode($script);

			$result->debug = $script;
			if($jscript) $result->json = $jscript;
		}

		if(isset($json->usage)) {
			$result->tokens = [
				"prompt_tokens" => $json->usage->prompt_tokens ?? $json->usage->input_tokens ?? 0,
				"completion_tokens" => $json->usage->completion_tokens ?? $json->usage->output_tokens ?? 0,
			];
		}

		return $result;
	}

	/**
	 * Extract generated text from OpenAI Chat Completions or Responses API payloads.
	 *
	 * @param stdClass|null $json The decoded response.
	 * @return string|null The generated text, if found.
	 */
	protected function extractTextFromResponse($json) {
		if(!is_object($json)) return null;

		if(isset($json->choices[0]->message->content) && is_string($json->choices[0]->message->content)) {
			return trim($json->choices[0]->message->content);
		}

		if(isset($json->choices[0]->text) && is_string($json->choices[0]->text)) {
			return trim($json->choices[0]->text);
		}

		if(isset($json->output_text) && is_string($json->output_text)) {
			return trim($json->output_text);
		}

		if(!isset($json->output) || !is_array($json->output)) return null;

		$text = $this->extractTextFromResponsesOutput($json->output, true);
		if($text !== null) return $text;

		return $this->extractTextFromResponsesOutput($json->output, false);
	}

	/**
	 * Extract text from a Responses API output array.
	 *
	 * @param array $output The Responses API output array.
	 * @param bool $messageOnly Whether to only inspect final message items.
	 * @return string|null The generated text, if found.
	 */
	protected function extractTextFromResponsesOutput($output, $messageOnly) {
		foreach($output as $item) {
			if(!is_object($item)) continue;
			if($messageOnly && (!isset($item->type) || $item->type !== "message")) continue;
			if(!isset($item->content)) continue;

			if(is_string($item->content) && trim($item->content) !== "") {
				return trim($item->content);
			}

			if(!is_array($item->content)) continue;

			foreach($item->content as $contentItem) {
				if(!is_object($contentItem)) continue;
				if(isset($contentItem->text) && is_string($contentItem->text) && trim($contentItem->text) !== "") {
					return trim($contentItem->text);
				}
			}
		}

		return null;
	}

	/**
	 * Normalize model text before decoding JSON.
	 *
	 * @param string $text The generated text.
	 * @return string Cleaned JSON text.
	 */
	protected function cleanJsonText($text) {
		$text = trim($text);
		$text = str_replace("\\n", "", $text);
		$text = str_replace("\\r", "", $text);
		$text = str_replace("\\t", "", $text);
		$text = str_replace("```json", "", $text);
		$text = str_replace("`", "", $text);
		return trim($text);
	}

	/**
	 * Send a request to the model using CURL.
	 * 
	 * @param array $headers The request headers.
	 * @param stdClass $body The request body.
	 * @return stdClass The response from the model.
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
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
		// Timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	
		$response = curl_exec($ch);

		return json_decode($response);
	}

	/**
	 * Extract a JSON object from a string.
	 * 
	 * @param string $input The input string.
	 * @return string The JSON object.
	 */
	function extractJsonFromString($input) {
		// Regular expression to find JSON objects in the string
		$pattern = '/\{(?:[^{}]|(?R))*\}/';
	
		// Perform the regular expression match
		if (preg_match($pattern, $input, $matches)) {
			$jsonString = $matches[0];
			return $jsonString;
		} else {
			// Return the original string if no JSON object is found
			return $input;
		}
	}

	/**
	 * Save an image from a base64 string.
	 * 
	 * @param string $base64 The base64 string.
	 * @return string The path to the saved image.
	 */
	protected function saveImageFromBase64($base64, $modelId) {
		$saveDir = 'backgrounds';
		$output_dir = dirname(__DIR__, 2) . '/assets/' . $saveDir . '-full';
		$absolute_path = '/assets/' . $saveDir . '-full';

		if (!file_exists($output_dir)) {
			mkdir($output_dir, 0777, true);
		}

		$i = 1;
		while (file_exists("$output_dir/$modelId" . '_' . "$i.png")) {
			$i++;
		}

		if (!is_string($base64) || trim($base64) === "") {
			throw new Exception("Image response did not contain base64 data.");
		}

		$base64 = trim($base64);
		if (strpos($base64, 'base64,') !== false) {
			$base64 = substr($base64, strpos($base64, 'base64,') + 7);
		}

		$image_data = base64_decode($base64, true);
		if ($image_data === false || strlen($image_data) === 0) {
			throw new Exception("Image response contained invalid base64 data.");
		}

		// TODO: Send image as url encoded base64 and modify the save script to handle.
		$image_path = "$output_dir/$modelId" . '_' . "$i.png";

		$file = fopen($image_path, 'wb');
		if (!$file) {
			throw new Exception("Unable to open image file for writing.");
		}

		$bytesWritten = fwrite($file, $image_data);
		fclose($file);

		if ($bytesWritten === false || $bytesWritten !== strlen($image_data) || filesize($image_path) === 0) {
			@unlink($image_path);
			throw new Exception("Unable to write generated image data.");
		}

		$image_path = "$absolute_path/$modelId" . '_' . "$i.png";

		return $image_path;	
	}
}
