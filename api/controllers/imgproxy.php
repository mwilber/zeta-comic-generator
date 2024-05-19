<?php
/**
 * Proxies a remote image by fetching it and returning the content.
 *
 * This function is used to fetch a remote image and return its contents. It
 * checks if a GET parameter `url` is provided, and if so, it fetches the image 
 * from the remote URL and returns the contents with the appropriate MIME type.
 */
	$remoteImage = $_GET['url'];
	if (!$remoteImage) die;
	$imginfo = getimagesize($remoteImage);
	header("Content-type: ".$imginfo['mime']);
	$output = readfile($remoteImage);
	echo $output;
?>