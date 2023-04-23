<?php

ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

// Load the two source images
$image1 = imagecreatefrompng('backgrounds/643c4de59154b.png');
$image2 = imagecreatefrompng('character_assets/sitting.png');

// Get the dimensions of the images
$width1 = imagesx($image1);
$height1 = imagesy($image1);
$width2 = imagesx($image2);
$height2 = imagesy($image2);

// Scale the second image to the same size as the first image
if ($width1 != $width2 || $height1 != $height2) {
    $tempImage = imagecreatetruecolor($width1, $height1);
	imagefill($tempImage,0,0,0x7fff0000);
    imagecopyresampled($tempImage, $image2, 0, 0, 0, 0, $width1, $height1, $width2, $height2);
    imagedestroy($image2);
    $image2 = $tempImage;
}

// Create a new blank image that can fit both images
$newWidth = $width1;
$newHeight = $height1;
$newImage = imagecreatetruecolor($newWidth, $newHeight);

// Copy the first image onto the new image
imagecopy($newImage, $image1, 0, 0, 0, 0, $width1, $height1);

// Copy the second image onto the new image, layered on top of the first image
imagecopy($newImage, $image2, 0, 0, 0, 0, $width1, $height1);

// Save the new image to the file system
imagepng($newImage, 'thumbnails/thumb_xx.png');

// Free up memory
imagedestroy($image1);
imagedestroy($image2);
imagedestroy($newImage);

?>