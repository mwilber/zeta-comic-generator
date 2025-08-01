<?php
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
		//$stmt = $db->prepare("INSERT INTO `comics` (`title`,`prompt`,`json`) VALUES (".$db->quote($_POST["title"]).",".$db->quote($_POST["prompt"]).",".$db->quote($jsonData).");");
        $stmt = $db->prepare("UPDATE `comics` SET title=".$db->quote($_POST["title"]).", prompt=".$db->quote($_POST["prompt"]).", json=".$db->quote($jsonData)." WHERE id=".$db->quote($_POST["id"]).";");
		// execute query
		$stmt->execute();
		// $output->response->comicId = $db->lastInsertId();
		// $output->response->permalink = md5($output->response->comicId);
		// // Insert a hash of the id for the permalink
		// $stmt = $db->prepare("UPDATE `comics` SET `permalink`=".$db->quote($output->response->permalink)." WHERE `id`=".$output->response->comicId.";");
		// // execute query
		// $stmt->execute();
	
		//echo $comicId;

		// if($output->response->comicId > 0) {
		// 	// Save the background images
		// 	for($idx = 0; $idx < 3; $idx++) {
		// 		$bkgUrl = $_POST["bkg".($idx + 1)];
		// 		$panel = ($idx + 1);

		// 		try {
		// 			$filename = downloadImage($bkgUrl);
		// 			//echo "Image saved as: " . $filename;
		// 		} catch (Exception $e) {
		// 			$output->error = "Error getting background image: " . $e->getMessage();
		// 		}
			
		// 		if(isset($filename)){
		// 			// prepare query statement
		// 			$stmt = $db->prepare("INSERT INTO `backgrounds` (`comic_id`, `panel`, `filename`) VALUES ('".$output->response->comicId."', '".$panel."', '".$filename."');");
		// 			// execute query
		// 			$stmt->execute();
		// 		}

		// 		if($idx == 1){
		// 			//Save the images to composite into a thumbnail
		// 			renderThumbnail($output->response->permalink, "../assets/backgrounds/".$filename, "../assets/character_art/".$_POST["fg".($idx + 1)]);
		// 		}
		// 	}
		// }
        $output->error = "";
		$output->response->values = $_POST["id"];
	}

?>