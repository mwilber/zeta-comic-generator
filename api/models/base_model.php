<?php
/**
 * Base class for AI model interactions. Based on the OpenAI API.
 */
class BaseModel {
    public $modelName;
    public $apiUrl;
    protected $apiKey;
    protected $responseFormat = "json_object";

    function sendPrompt($prompt, $messages) {
        $result = new stdClass;
        $response = $this->textComplete($this->apiKey, $messages);
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

    protected function textComplete($key, $messages) {
        $response = new stdClass;
        $response->data = null;
        $response->script = null;
    
        $ch = curl_init();
        $headers = array(
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        );
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
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