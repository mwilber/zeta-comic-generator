<?php
	use Aws\BedrockRuntime\BedrockRuntimeClient;
	//use Exception;

	require __DIR__ . '/includes/key.php';
	require __DIR__ . '/../vendor/autoload.php';

	$bedrockRuntimeClient = new BedrockRuntimeClient([
		'region' => 'us-east-1',
		'version' => 'latest',
		//'profile' => $profile,
		'credentials' => [
			'key'    => AWS_ACCESS_KEY,
			'secret' => AWS_SECRET_KEY,
		],
	]);

	echo "\n\nAmazon Stablediffusion Image Generation:\n";
	//$image_prompt = 'Create an image of an intergalactic villain\'s lair filled with advanced technology and multiple screens displaying different landscapes of Earth, including forests, cities, and oceans. Include a large red button on a central console.';
	$image_prompt = 'A grassy field with mountains in the distance.';
    $seed = rand(0, 2147483647);
	//$base64 = invokeTitanImage($image_prompt, $titanSeed);
    $style_preset = ['3d-model', 'analog-film', 'anime', 'cinematic', 'comic-book', 'digital-art', 'enhance', 'fantasy-art', 'isometric', 'line-art', 'low-poly', 'modeling-compound', 'neon-punk', 'origami', 'photographic', 'pixel-art', 'tile-texture'];
	$base64_image_data = "";

    try {
        $modelId = 'stability.stable-diffusion-xl-v1';

        // StableDiffusion params doc: https://platform.stability.ai/docs/api-reference#tag/Text-to-Image/operation/textToImage
        $body = [
            'text_prompts' => [
                ['text' => $image_prompt]
            ],
            'seed' => $seed,
            'cfg_scale' => 10,
            'steps' => 30,
            'height' => 512,
            'width' => 512
        ];

        if ($style_preset) {
            $body['style_preset'] = $style_preset[4];
        }

        $result = $bedrockRuntimeClient->invokeModel([
            'contentType' => 'application/json',
            'body' => json_encode($body),
            'modelId' => $modelId,
        ]);

        $response_body = json_decode($result['body']);

        $base64_image_data = $response_body->artifacts[0]->base64;
    } catch (Exception $e) {
        echo "Error: ({$e->getCode()}) - {$e->getMessage()}\n";
    }

	$output_dir = "../assets/titan";

    if (!file_exists($output_dir)) {
        mkdir($output_dir);
    }

    $i = 1;
    while (file_exists("$output_dir/$model_id" . '_' . "$i.png")) {
        $i++;
    }

    $image_data = base64_decode($base64_image_data);

    $image_path = "$output_dir/$model_id" . '_' . "$i.png";

    $file = fopen($image_path, 'wb');
    fwrite($file, $image_data);
    fclose($file);


	//$image_path = $this->saveImage($base64, 'amazon.titan-image-generator-v1');
	echo "The generated images have been saved to $image_path";

	echo "success";
?>

<img src="data:image/png;base64,<?php echo $base64_image_data; ?>" />