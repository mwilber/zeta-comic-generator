<?php
	$request = $_SERVER['REQUEST_URI'];
	//echo $request;

	$meta = new stdClass();
	$meta->siteTitle = "Zeta Comic Generator";
	$meta->title = "Zeta Comic Generator";
	$meta->siteUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]";
	$meta->url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$meta->image = "https://comicgenerator.greenzeta.com";
	$meta->description = "InsertDescriptionHere";
	$meta->imageDescription = "InsertImageDescriptionHere";

	$path = explode('/', $request);

	// Validate the path
	if(isset($path[1])) {
		if($path[1] == 'detail' && (!isset($path[2]) || !$path[2])) {
			// If no detail hash, display the home page
			$path[1] = 'home';
		} else {
			// Grab share metadata
			require __DIR__ . '/api/includes/db.php';

			$database = new Database();
			$db = $database->getConnection();

			try {
				$stmt = $db->prepare("SELECT * FROM `comics` WHERE permalink = :id");
				$stmt->bindParam(':id', $path[2], PDO::PARAM_STR);
				$stmt->execute();

				// Fetch the single record as an object
				$result = $stmt->fetch(PDO::FETCH_OBJ);

				if ($result && isset($result->json)) {
					$meta->hash = $result->permalink;
					$meta->title = $result->title;
					$meta->image = $meta->siteUrl."/assets/thumbnails/thumb_".$result->permalink.".png";
				}
			} catch(PDOException $e) {	}
		}
	}

	// 
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
    <title><?php echo $meta->siteTitle; ?></title>

	<meta property="og:url" content="<?php echo $meta->url; ?>">
	<meta property="og:type" content="website">
	<meta property="og:title" content="<?php echo $meta->title; ?>">
	<meta property="og:image" content="<?php echo $meta->image; ?>">
	<meta property="og:image:alt" content="<?php echo $meta->title; ?>">
	<meta property="og:description" content="<?php echo $meta->description; ?>">
	<meta property="og:site_name" content="<?php echo $meta->siteTitle; ?>">
	<meta property="og:locale" content="en_US">

	<meta name="twitter:card" content="summary">
	<meta name="twitter:site" content="@greenzeta">
	<meta name="twitter:creator" content="@greenzeta">
	<meta name="twitter:url" content="<?php echo $meta->url; ?>">
	<meta name="twitter:title" content="<?php echo $meta->title; ?>">
	<meta name="twitter:description" content="<?php echo $meta->description; ?>">
	<meta name="twitter:image" content="<?php echo $meta->image; ?>">
	<meta name="twitter:image:alt" content="<?php echo $meta->imageDescription; ?>">

    <link rel="stylesheet" href="/style.css">
  </head>
  <body class="<?php echo $path[1] ?>">
	<div class="halftone"></div>
	<!-- <script src="index.js"></script> -->
	<?php
		// Render the header
		require __DIR__ . '/templates/header.php';
	?>
	<div class="content">
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
	</div>
  </body>
</html>