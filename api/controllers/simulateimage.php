<?php
// pause execution for 2 seconds to simulate the remote API response
sleep(2);

$query = $_POST["query"];
$output->prompt = $query;
// Record the model that was used
$output->model = "simulation";
$output->error = "";

$output->data = new stdClass;
$output->data->url = "https://zeta-comic-generator.s3.us-east-2.amazonaws.com/backgrounds/66397bcbcaab1.png";

$output->json = $output->data;
?>