<?php
	$remoteImage = $_GET['url'];
	if (!$remoteImage) die;
	$imginfo = getimagesize($remoteImage);
	header("Content-type: ".$imginfo['mime']);
	$output = readfile($remoteImage);
	echo $output;
?>