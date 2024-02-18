<?php
	use Aws\S3\S3Client;
	use Aws\Exception\AwsException;

	function uploadS3($localPath, $fileName, $folderName) {

		$keyName = basename($fileName); // e.g., 'image.jpg'

		// // Download image
		// $imageData = file_get_contents($remoteUrl);
		// if ($imageData === false) {
		// 	die('Failed to download image');
		// }
	
		// // TODO: Store the image in its original folder
		// // Temporarily save the file
		// $tempFilePath = sys_get_temp_dir() . '/' . $keyName;
		// file_put_contents($tempFilePath, $imageData);
	
		// Instantiate the S3 client with your AWS credentials
		$s3Client = new S3Client([
			'version' => 'latest',
			'region'  => AWS_REGION,
			'credentials' => [
				'key'    => AWS_ACCESS_KEY,
				'secret' => AWS_SECRET_KEY,
			],
		]);
	
		try {
			// Upload the image to your bucket
			$result = $s3Client->putObject([
				'Bucket' => BUCKET_NAME,
				'Key'    => $folderName . $keyName,
				'SourceFile' => $localPath,
				'ACL'    => 'public-read', // or use 'private'
			]);
	
			//echo "Image uploaded successfully. Image URL: " . $result['ObjectURL'] . PHP_EOL;
		} catch (AwsException $e) {
			// Output error message if something goes wrong.
			// Suppressing error messages for now.
			//echo $e->getMessage() . PHP_EOL;
			return false;
		} finally {
			// Clean up: delete temporary file
			// Keeping the file on the server as a backup for now.
			//unlink($tempFilePath);
			return true;
		}
	}

	//uploadS3('https://softwaretested.com/wp-content/uploads/2021/04/Testing.jpg', 'test.jpg', 'backgrounds/');
	//https://zeta-comic-generator.s3.us-east-2.amazonaws.com/backgrounds/65d2236b73156.png
?>