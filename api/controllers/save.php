<?php

	function downloadImage($url, $saveDir = 'backgrounds/') {
		$savePath = '../assets/' . $saveDir;
		// Create the directory if it doesn't exist
		if (!file_exists($savePath)) {
			mkdir($savePath, 0777, true);
		}

		// Initialize cURL session
		$ch = curl_init($url);

		// Set cURL options
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		// Get the image content
		$imageData = curl_exec($ch);

		// Check for errors
		if (curl_errno($ch)) {
			throw new Exception(curl_error($ch));
		}

		// Close the cURL session
		curl_close($ch);

		// Generate a unique file name
		$fileName = uniqid() . '.png';
		$filePath = $savePath . $fileName;

		// Save the image to the local filesystem
		file_put_contents($filePath, $imageData);

		uploadS3($filePath, $fileName, $saveDir);

		return $fileName;
	}

	function renderThumbnail($id, $background, $foreground) {
		// Load the two source images
		$image1 = imagecreatefrompng($background);
		$image2 = imagecreatefrompng($foreground);

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
		imagepng($newImage, '../assets/thumbnails/thumb_'.$id.'.png');

		uploadS3('../assets/thumbnails/thumb_'.$id.'.png', 'thumb_'.$id.'.png', 'thumbnails/');

		// Free up memory
		imagedestroy($image1);
		imagedestroy($image2);
		imagedestroy($newImage);
	}


	//$output = json_decode('{"response": {}, "error": null}');
    $output->response = new stdClass;

	$database = new Database();
	$db = $database->getConnection();

	if(!isset($_POST["script"])) {
		$output->error = "Value `script` not found in POST";
		$output->response->values = $_POST;
	} else {
		$jsonData = $_POST["script"];
	
		// prepare query statement
		$stmt = $db->prepare("INSERT INTO `comics` (`title`,`prompt`,`json`) VALUES (".$db->quote($_POST["title"]).",".$db->quote($_POST["prompt"]).",".$db->quote($jsonData).");");
		// execute query
		$stmt->execute();
		$output->response->comicId = $db->lastInsertId();
		$output->response->permalink = md5($output->response->comicId);
		// Insert a hash of the id for the permalink
		$stmt = $db->prepare("UPDATE `comics` SET `permalink`=".$db->quote($output->response->permalink)." WHERE `id`=".$output->response->comicId.";");
		// execute query
		$stmt->execute();
	
		//echo $comicId;

		if($output->response->comicId > 0) {
			// Save the background images
			for($idx = 0; $idx < 3; $idx++) {
				$bkgUrl = $_POST["bkg".($idx + 1)];
				$panel = ($idx + 1);

				try {
					$fileName = downloadImage($bkgUrl);
					//echo "Image saved as: " . $fileName;
				} catch (Exception $e) {
					$output->error = "Error getting background image: " . $e->getMessage();
				}
			
				if(isset($fileName)){
					// prepare query statement
					$stmt = $db->prepare("INSERT INTO `backgrounds` (`comic_id`, `panel`, `filename`) VALUES ('".$output->response->comicId."', '".$panel."', '".$fileName."');");
					// execute query
					$stmt->execute();
				}

				if($idx == 1){
					//Save the images to composite into a thumbnail
					renderThumbnail($output->response->permalink, "../assets/backgrounds/".$fileName, "../assets/character_art/".$_POST["fg".($idx + 1)]);
				}
			}
		}
	}

?>
