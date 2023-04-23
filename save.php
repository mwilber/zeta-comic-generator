<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ERROR);

	// include database and object files
	include_once './db.php';

	function downloadImage($url, $savePath = './backgrounds/') {
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
		$filename = uniqid() . '.png';
		$filePath = $savePath . $filename;

		// Save the image to the local filesystem
		file_put_contents($filePath, $imageData);

		return $filename;
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
		imagepng($newImage, 'thumbnails/thumb_'.$id.'.png');

		// Free up memory
		imagedestroy($image1);
		imagedestroy($image2);
		imagedestroy($newImage);
	}

	// required headers
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	$output = json_decode('{"response": {}, "error": null}');

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
	
		//echo $comicId;

		if($output->response->comicId > 0) {
			// Save the background images
			for($idx = 0; $idx < 3; $idx++) {
				$bkgUrl = $_POST["bkg".($idx + 1)];
				$panel = ($idx + 1);

				try {
					$filename = downloadImage($bkgUrl);
					//echo "Image saved as: " . $filename;
				} catch (Exception $e) {
					$output->error = "Error getting background image: " . $e->getMessage();
				}
			
				if(isset($filename)){
					// prepare query statement
					$stmt = $db->prepare("INSERT INTO `backgrounds` (`comic_id`, `panel`, `filename`) VALUES ('".$output->response->comicId."', '".$panel."', '".$filename."');");
					// execute query
					$stmt->execute();
				}

				if($idx == 1){
					//Save the images to composite into a thumbnail
					renderThumbnail($output->response->comicId, "backgrounds/".$filename, "character_assets/".$_POST["fg".($idx + 1)]);
				}
			}
			// Save the thumbnail image
			// $imageData = base64_decode(str_replace("data:image/png;base64,", "", $_POST["thumbnail"]));
			// $fileName = "./thumbnails/thumb_".$output->response->comicId.".png";
			// $file = fopen($fileName, "wb");
			// fwrite($file, $imageData);
			// fclose($file);
		}
	}


	echo json_encode($output);

?>
