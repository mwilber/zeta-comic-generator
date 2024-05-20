<?php
	use Aws\S3\S3Client;
	use Aws\Exception\AwsException;

	function uploadS3($localPath, $fileName, $folderName) {

		$keyName = basename($fileName); // e.g., 'image.jpg'
	
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
?>