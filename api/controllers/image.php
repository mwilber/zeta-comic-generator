<?php
if(isset($_POST["mode"])) {
	$mode = $_POST["mode"];
} else {
	$mode = "production";
}

$query = $_POST["query"];
//print_r($query);

if(!$query){
    $query = "A grassy knoll";
}

if($mode == "simulation") {
	$simJson = "{
		\"error\": null,
		\"data\": [
		  {
			\"url\": \"http://zcgdev.greenzeta.com/assets/backgrounds/646d55263811a.png\"
		  }
		]
	}";
	$simResponse = json_decode($simJson);
	$output->data = $simResponse->data;
	sleep(1);
} else {
	// For testing
	if(false){
		switch($query){
			case "A faraway view of Earth and the Sun with a starry night sky":
				echo '{"data":[{"url":"https://proxygpt.greenzeta.com/zeta-comic-generator/testimg/img-5qktQ0jDtAYNJ1L7ULTHhiqC.png"}]}';
				break;
			case "A mathematical equation written on an old parchment with quill pen":
				echo '{"data":[{"url":"https://proxygpt.greenzeta.com/zeta-comic-generator/testimg/img-iA5oi5pnlELNq7D3ZHxbSeLg.png"}]}';
				break;
			case "A view of Earth with the stars and planets around it":
				echo '{"data":[{"url":"https://proxygpt.greenzeta.com/zeta-comic-generator/testimg/img-yT9OR0KXefyxAfTnanZRI9VX.png"}]}';
				break;
		}
		die;
	}

	$url = "https://api.openai.com/v1/images/generations";

	$ch = curl_init();
	$headers = array(
		'Authorization: Bearer ' . $OPENAI_KEY,
		'Content-Type: application/json',
	);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$body = '{
			"prompt": "'.$query.'",
			"n": 1,
			"size": "512x512"
	}';

	//print_r($body);

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
	curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// Timeout in seconds
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);

	$json = curl_exec($ch);


	//$json = "{\"hello\": \"world\"}";
	$data = json_decode($json);
	//echo $data->data[0]->url; 
	$output->error = $data->error;
	$output->data = $data->data;
}
?>