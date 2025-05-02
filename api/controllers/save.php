<?php
	function downloadImage($url, $saveDir = 'backgrounds') {
		$savePath = '../assets/' . $saveDir . '/';
		$backupPath = '../assets/' . $saveDir . '-full/';
		// Create the directory if it doesn't exist
		if (!file_exists($savePath)) {
			mkdir($savePath, 0777, true);
		}
		if (!file_exists($backupPath)) {
			mkdir($backupPath, 0777, true);
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
		$resizedFilePath = $savePath . $fileName;
		$backupFilePath = $backupPath . $fileName;

		// Save the image to the local filesystem
		file_put_contents($backupFilePath, $imageData);
		uploadS3($backupFilePath, $fileName, $saveDir . '-full/');

		// Load the image
		$image = imagecreatefromstring($imageData);
		if ($image === false) {
			die('Failed to create image from downloaded data');
		}

		// Calculate resize dimensions (assuming square resize)
		$width = imagesx($image);
		$height = imagesy($image);
		$minSize = min($width, $height);
		$resizeTo = 512; // New size for resized image

		// Create a new true color image
		$resizedImage = imagecreatetruecolor($resizeTo, $resizeTo);

		// Resize and crop image
		imagecopyresampled($resizedImage, $image, 0, 0, ($width-$minSize)/2, ($height-$minSize)/2, $resizeTo, $resizeTo, $minSize, $minSize);

		// Save the resized image
		if (!imagepng($resizedImage, $resizedFilePath)) {
			die('Failed to save resized image');
		}

		uploadS3($resizedFilePath, $fileName, $saveDir . '/');

		//echo "Original and resized images saved successfully.";

		// Clean up
		imagedestroy($image);
		imagedestroy($resizedImage);

		return $fileName;
	}

	function saveLocalImage($local_path, $saveDir = 'backgrounds') {
		$savePath = '../assets/' . $saveDir . '/';
		$backupPath = '../assets/' . $saveDir . '-full/';
		// Create the directory if it doesn't exist
		if (!file_exists($savePath)) {
			mkdir($savePath, 0777, true);
		}
		if (!file_exists($backupPath)) {
			mkdir($backupPath, 0777, true);
		}

		//$image_data = base64_decode($base64_image_data);

		// Generate a unique file name
		$fileName = uniqid() . '.png';
		$resizedFilePath = $savePath . $fileName;
		$backupFilePath = $backupPath . $fileName;

		// $image_path = "$output_dir/$modelId" . '_' . "$i.png";

		// Save the local image backup to s3
		uploadS3($local_path, $fileName, $saveDir . '-full/');

		// Load the image
		$image = imagecreatefrompng($local_path);
		if ($image === false) {
			die('Failed to create image from downloaded data');
		}

		// Calculate resize dimensions (assuming square resize)
		$width = imagesx($image);
		$height = imagesy($image);
		$minSize = min($width, $height);
		$resizeTo = 512; // New size for resized image

		// Create a new true color image
		$resizedImage = imagecreatetruecolor($resizeTo, $resizeTo);

		// Resize and crop image
		imagecopyresampled($resizedImage, $image, 0, 0, ($width-$minSize)/2, ($height-$minSize)/2, $resizeTo, $resizeTo, $minSize, $minSize);

		// Save the resized image
		if (!imagepng($resizedImage, $resizedFilePath)) {
			die('Failed to save resized image');
		}

		uploadS3($resizedFilePath, $fileName, $saveDir . '/');

		//echo "Original and resized images saved successfully.";

		// Clean up
		imagedestroy($image);
		imagedestroy($resizedImage);

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
		$stmt = $db->prepare("INSERT INTO `comics` (`title`,`prompt`,`json`,`summary`,`storyId`) VALUES (".$db->quote($_POST["title"]).",".$db->quote($_POST["prompt"]).",".$db->quote($jsonData).",".$db->quote($_POST["summary"]).",".$db->quote($_POST["storyId"]).");");
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
					//$fileName = downloadImage($bkgUrl);
					if (isset($bkgUrl[0]) && $bkgUrl[0] === '/') {
						$fileName = saveLocalImage('..'.$bkgUrl);
					} else {
						// Pass $bkgUrl to downloadImage function
						$fileName = downloadImage($bkgUrl);
					}
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

		$continuityRaw = $_POST["continuity"];
		$output->response->continuity = $continuityRaw;
		if(isset($continuityRaw) && !empty($continuityRaw)) {
			$continuity = json_decode($continuityRaw);
			// Verify that $continuity is an object
			if (is_object($continuity)) {
				$categories = array('alpha', 'event');
				// Retrieve records from the `category` table where `alias` matches any value in the $categories array
				$placeholders = implode(',', array_fill(0, count($categories), '?'));
				$stmt = $db->prepare("SELECT * FROM `categories` WHERE `alias` IN ($placeholders);");
				$stmt->execute($categories);
				$categoryRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
				foreach ($categoryRecords as $category) {
					$alias = $category['alias'];
					$continuityItems = $continuity->$alias;
					foreach ($continuityItems as $item) {
						// prepare query statement
						$stmt = $db->prepare("INSERT INTO `continuity` (`categoryId`, `description`) VALUES (".$db->quote($category['id']).", ".$db->quote($item).");");
						// execute query
						$stmt->execute();
      
						$itemId = $db->lastInsertId();
						// Update the `permalink` field with an md5 hash of the new ID
						$stmt = $db->prepare("UPDATE `continuity` SET `permalink`=".$db->quote(md5($itemId))." WHERE `id`=".$itemId.";");
						$stmt->execute();

						// Insert a record into the table `comic_continuity`. The table has two fields: `comicId` and `continuityId`
						$stmt = $db->prepare("INSERT INTO `comic_continuity` (`comicId`, `continuityId`) VALUES ('".$output->response->comicId."', '".$itemId."');");
						// execute query
						$stmt->execute();
					}
				}
			} else {
				//$output->error = "Invalid memories format";
			}
		}

		$memoriesRaw = $_POST["memory"];
		$output->response->memories = $memoriesRaw;
		if(isset($memoriesRaw) && !empty($memoriesRaw)) {
			$memories = json_decode($memoriesRaw);
			// Verify that $memories is an array
			if (is_array($memories)) {
				foreach ($memories as $memory) {
					// If $memory->id is not set, or not a number, skip this iteration
					if (!isset($memory->id) || !is_numeric($memory->id)) {
						continue;
					}
					// Check for existing record
					$stmt = $db->prepare("SELECT `id` FROM `continuity` WHERE `id` = ".$memory->id.";");
					$stmt->execute();
					$existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

					if ($existingRecord) {
						$memoryId = $existingRecord['id'];
					}
					// Insert a record into the table `comic_continuity`. The table has two fields: `comicId` and `continuityId`
					$stmt = $db->prepare("INSERT INTO `comic_continuity` (`comicId`, `continuityId`) VALUES ('".$output->response->comicId."', '".$memoryId."');");
					// execute query
					$stmt->execute();
				}
			} else {
				//$output->error = "Invalid memories format";
			}
		}
	}

?>
