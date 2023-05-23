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
			\"url\": \"https://oaidalleapiprodscus.blob.core.windows.net/private/org-e1KBDpgBATQAfNWLSHcwhJpH/user-mzXd7EfURH2hiDUV6ftfcjOM/img-Gud5zCkdATWZpYyKgTJQM8B7.png?st=2023-05-23T22%3A01%3A56Z&se=2023-05-24T00%3A01%3A56Z&sp=r&sv=2021-08-06&sr=b&rscd=inline&rsct=image/png&skoid=6aaadede-4fb3-4698-a8f6-684d7786b067&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2023-05-23T20%3A54%3A53Z&ske=2023-05-24T20%3A54%3A53Z&sks=b&skv=2021-08-06&sig=xO1rFmYaupaK8Fyo0ghXnPFNU/94/gl6HTvqP3uxvZE%3D\"
		  }
		]
	}";
	$simResponse = json_decode($simJson);
	$output->data = $simResponse->data;
	sleep(3);
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
			"size": "256x256"
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