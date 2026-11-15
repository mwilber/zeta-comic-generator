<?php

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
    imagepng($newImage, dirname(__DIR__, 2) . '/assets/thumbnails/thumb_'.$id.'.png');

	// Upload to S3
	uploadS3(dirname(__DIR__, 2) . '/assets/thumbnails/thumb_'.$id.'.png', 'thumb_'.$id.'.png', 'thumbnails/');

    // Free up memory
    imagedestroy($image1);
    imagedestroy($image2);
    imagedestroy($newImage);
}

$output->response = new stdClass;
require_once __DIR__ . '/../includes/editor_auth.php';
require_once __DIR__ . '/../includes/write_security.php';
require_once __DIR__ . '/../includes/image_security.php';
if (!comicRequireEditor()) { $output->error = 'Editor authentication required'; return; }
try {
    comicEditorWriteRequest();
    if (!is_string($_POST['id'] ?? null) || !preg_match('/\A[a-f0-9]{32}\z/', $_POST['id'])) throw new InvalidArgumentException('Invalid permalink');
    $background = comicAssetFile('backgrounds', $_POST['background'] ?? null);
    $foreground = comicAssetFile('character_art', ($_POST['foreground'] ?? '') . '.png');
    comicImageInfo(file_get_contents($background));
    comicImageInfo(file_get_contents($foreground));
    $db = (new Database())->getConnection();
    $stmt = $db->prepare('SELECT id FROM comics WHERE permalink = ?');
    $stmt->execute([$_POST['id']]);
    if (!$stmt->fetchColumn()) throw new InvalidArgumentException('Comic not found');
    renderThumbnail($_POST['id'], $background, $foreground);
} catch (Throwable $e) {
    if (http_response_code() < 400) http_response_code(400);
    $output->error = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Thumbnail creation failed';
}
