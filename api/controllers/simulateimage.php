<?php
/**
 * Used only when the global constant SIMULATION_MODE is set to `true`.
 * 
 * Simulates a remote API response by pausing execution for 2 seconds, then
 * populates the $output object with the simulated response data.
 *
 * Provides response identical to `generateimage.php`.
 */

// pause execution for 2 seconds to simulate the remote API response
sleep(2);

$query = $_POST["query"];
$output->prompt = $query;
// Record the model that was used
$output->model = "simulation";
$output->error = "";

$output->data = new stdClass;
$output->data->url = BUCKET_URL."/backgrounds/66397bcbcaab1.png";

$output->json = $output->data;
?>