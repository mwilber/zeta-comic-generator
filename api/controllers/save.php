<?php
	function assetPath($path = '') {
		return dirname(__DIR__, 2) . '/assets' . $path;
	}

	require_once __DIR__ . '/../includes/image_security.php';
	require_once __DIR__ . '/../includes/write_security.php';

    function saveImageBytes($imageData, $saveDir = 'backgrounds') {
        comicImageInfo($imageData);
        $savePath = assetPath('/' . $saveDir . '/');
        $backupPath = assetPath('/' . $saveDir . '-full/');
        foreach ([$savePath, $backupPath] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Cannot create image directory');
        }
        $fileName = bin2hex(random_bytes(16)) . '.png';
        $image = @imagecreatefromstring($imageData);
        if (!$image) throw new RuntimeException('Cannot decode image');
        // Re-encode before any upload: no original untrusted bytes are published.
        if (!imagepng($image, $backupPath . $fileName)) throw new RuntimeException('Cannot save image');
        $size = min(imagesx($image), imagesy($image));
        $resized = imagecreatetruecolor(512, 512);
        imagecopyresampled($resized, $image, 0, 0, (imagesx($image)-$size)/2, (imagesy($image)-$size)/2, 512, 512, $size, $size);
        if (!imagepng($resized, $savePath . $fileName)) throw new RuntimeException('Cannot save image');
        imagedestroy($image);
        imagedestroy($resized);
        uploadS3($backupPath . $fileName, $fileName, $saveDir . '-full/');
        uploadS3($savePath . $fileName, $fileName, $saveDir . '/');
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
		$thumbnailPath = assetPath('/thumbnails/thumb_'.$id.'.png');
		imagepng($newImage, $thumbnailPath);

		uploadS3($thumbnailPath, 'thumb_'.$id.'.png', 'thumbnails/');

		// Free up memory
		imagedestroy($image1);
		imagedestroy($image2);
		imagedestroy($newImage);
	}


	//$output = json_decode('{"response": {}, "error": null}');
	$output->response = new stdClass;

    try {
        comicWriteRequest();
        comicValidateScript($_POST);
        foreach (['summary' => 20000, 'continuity' => 65536, 'memory' => 65536, 'seriesId' => 20] as $key => $limit) {
            if (!isset($_POST[$key])) $_POST[$key] = '';
            if (!is_string($_POST[$key]) || strlen($_POST[$key]) > $limit) throw new InvalidArgumentException('Invalid ' . $key);
        }
        if ($_POST['seriesId'] !== '' && !ctype_digit($_POST['seriesId'])) throw new InvalidArgumentException('Invalid series');
        $foreground = comicAssetFile('character_art', $_POST['fg2'] ?? '');
        comicImageInfo(file_get_contents($foreground));
        comicSaveRateLimit();
        $backgroundBytes = [];
        for ($i = 1; $i <= 3; $i++) $backgroundBytes[] = comicReadImage($_POST['bkg' . $i] ?? null);
    } catch (Throwable $e) {
        if (http_response_code() < 400) http_response_code(400);
        $output->error = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to prepare comic';
        return;
    }

	$database = new Database();
	$db = $database->getConnection();
    try {
    $db->beginTransaction();

	if(!isset($_POST["script"])) {
		$output->error = "Value `script` not found in POST";
		$output->response->values = $_POST;
	} else {
		$jsonData = $_POST["script"];
	
        $stmt = $db->prepare('INSERT INTO comics (title, prompt, json, summary, seriesId) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$_POST['title'], $_POST['prompt'], $jsonData, $_POST['summary'], $_POST['seriesId']]);
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

                $fileName = saveImageBytes($backgroundBytes[$idx]);

				if(isset($fileName)){
					// prepare query statement
					$stmt = $db->prepare("INSERT INTO `backgrounds` (`comic_id`, `panel`, `filename`) VALUES ('".$output->response->comicId."', '".$panel."', '".$fileName."');");
					// execute query
					$stmt->execute();
				}

				if($idx == 1){
					//Save the images to composite into a thumbnail
					renderThumbnail($output->response->permalink, assetPath("/backgrounds/".$fileName), $foreground);
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
					$continuityItems = isset($continuity->$alias) && is_array($continuity->$alias)
						? $continuity->$alias
						: [];
					foreach ($continuityItems as $item) {
						// Models may return either a description string or an object with a
						// description property. Store only a non-empty description string.
						if (is_object($item) && isset($item->description) && is_string($item->description)) {
							$item = $item->description;
						}
						if (!is_string($item) || trim($item) === '') {
							continue;
						}

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
					$stmt = $db->prepare("SELECT `id` FROM `continuity` WHERE `id` = ?");
					$stmt->execute([$memory->id]);
					$existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

					if (!$existingRecord) continue;
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

    $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        $output->response = new stdClass;
        $output->error = 'Unable to save comic';
        error_log('Comic save failed: ' . get_class($e));
    }
?>
