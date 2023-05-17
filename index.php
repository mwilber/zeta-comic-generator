<?php
	$request = $_SERVER['REQUEST_URI'];
	//echo $request;

	$path = explode('/', $request);

	// Validate the path
	if(isset($path[1])) {
		if($path[1] == 'detail' && (!isset($path[2]) || !$path[2])) {
			// If no detail hash, display the home page
			$path[1] = 'home';
		}
	}

	//print_r($path);
?>
<html lang="en">
  <head>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-3PESRSPTLD"></script>
	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());

	gtag('config', 'G-3PESRSPTLD');
	</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Zeta Comic Generator</title>
    <link rel="stylesheet" href="/style.css">
  </head>
  <body class="<?php echo $path[1] ?> init">
	<!-- <script src="index.js"></script> -->
	<?php
		// Render the header
		require __DIR__ . '/templates/header.php';
	?>

	<?php
		// Render the view
		switch ($path[1]) {

			case 'detail':
			case 'gallery':
			case 'generate':
				require __DIR__ . '/views/'.$path[1].'.php';
				break;
		
			default:
				//http_response_code(404);
				//require __DIR__ . '/views/404.php';
				require __DIR__ . '/views/home.php';
				break;
		}
	?>
  </body>
</html>