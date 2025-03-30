<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

    require __DIR__ . '/api/includes/prompts.php';
	require __DIR__ . '/api/includes/characteractions.php';

	$request = $_SERVER['REQUEST_URI'];
	//echo $request;

	$version = "3.5.0";
	$meta = new stdClass();
	$meta->siteTitle = "Zeta Comic Generator";
	$meta->title = "Zeta Comic Generator";
	$meta->siteUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]";
	$meta->url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$meta->image = $meta->url . "assets/images/social_share_2_1024.png";
	$meta->description = "AI powered stories featuring a little green alien named Alpha. Create comics from your ideas.";
	$meta->imageDescription = "Zeta Comic Generator";

	$path = explode('/', $request);
	$isGemini = false;

	// Validate the path
	if(isset($path[1])) {
		if($path[1] == 'detail' && (!isset($path[2]) || !$path[2])) {
			// If no detail hash, display the home page
			$path[1] = 'home';
		} else {
			// Grab share metadata
            require __DIR__ . '/api/includes/key.php';
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
					$meta->description = "Check out my comic strip `" . $result->title . "` from Zeta Comic Generator. " . $meta->description;
					$meta->imageDescription = $result->title . " from " . $meta->imageDescription;
				}
			} catch(PDOException $e) {	}
		}
	}

	// 
	//print_r($path);
?>
<!DOCTYPE html>
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
	<meta name="description" content="<?php echo $meta->description; ?>">

	<meta property="og:url" content="<?php echo $meta->url; ?>">
	<meta property="og:type" content="website">
	<meta property="og:title" content="<?php echo $meta->title; ?>">
	<meta property="og:image" content="<?php echo $meta->image; ?>">
	<meta property="og:image:alt" content="<?php echo $meta->imageDescription; ?>">
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

	<link rel="stylesheet" href="/style.css?v=<?php echo $version ?>">
  </head>
  <body class="<?php echo $path[1] ?>" data-status="ready">
	<div class="halftone"></div>
	<!-- <script src="index.js"></script> -->
	<?php
		// Render the header
		require __DIR__ . '/templates/header.php';
	?>
	<main class="content">
	<?php
		// Render the view
		switch ($path[1]) {
			// Handle gemini path
			case 'gemini':
				// Handle gemini path. Display the gemini demo comic.
				$isGemini = true;
				$path[1] = 'detail';
				$path[2] = '0d7de1aca9299fe63f3e0041f02638a3';
			case 'about':
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
	</main>
	<div class="badges">
		<a href="http://twitter.com/greenzeta" class="badge twix" target="_blank"  rel="noopener noreferrer" aria-label="X profile link">
			<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M18.9014 1.16016H22.5816L14.5415 10.3493L24 22.8537H16.5941L10.7935 15.2699L4.15631 22.8537H0.473926L9.07356 13.0249L0 1.16016H7.59394L12.8372 8.09208L18.9014 1.16016ZM17.6098 20.651H19.649L6.48589 3.24719H4.29759L17.6098 20.651Z" fill="white"/>
			</svg>
		</a>
		<a href="https://www.instagram.com/greenzeta/" class="badge instagram" target="_blank"  rel="noopener noreferrer" aria-label="Instagram profile link">
			<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<g clip-path="url(#clip0_1432_8668)">
			<path d="M12 0C8.74 0 8.333 0.015 7.053 0.072C5.775 0.132 4.905 0.333 4.14 0.63C3.351 0.936 2.681 1.347 2.014 2.014C1.347 2.681 0.935 3.35 0.63 4.14C0.333 4.905 0.131 5.775 0.072 7.053C0.012 8.333 0 8.74 0 12C0 15.26 0.015 15.667 0.072 16.947C0.132 18.224 0.333 19.095 0.63 19.86C0.936 20.648 1.347 21.319 2.014 21.986C2.681 22.652 3.35 23.065 4.14 23.37C4.906 23.666 5.776 23.869 7.053 23.928C8.333 23.988 8.74 24 12 24C15.26 24 15.667 23.985 16.947 23.928C18.224 23.868 19.095 23.666 19.86 23.37C20.648 23.064 21.319 22.652 21.986 21.986C22.652 21.319 23.065 20.651 23.37 19.86C23.666 19.095 23.869 18.224 23.928 16.947C23.988 15.667 24 15.26 24 12C24 8.74 23.985 8.333 23.928 7.053C23.868 5.776 23.666 4.904 23.37 4.14C23.064 3.351 22.652 2.681 21.986 2.014C21.319 1.347 20.651 0.935 19.86 0.63C19.095 0.333 18.224 0.131 16.947 0.072C15.667 0.012 15.26 0 12 0ZM12 2.16C15.203 2.16 15.585 2.176 16.85 2.231C18.02 2.286 18.655 2.48 19.077 2.646C19.639 2.863 20.037 3.123 20.459 3.542C20.878 3.962 21.138 4.361 21.355 4.923C21.519 5.345 21.715 5.98 21.768 7.15C21.825 8.416 21.838 8.796 21.838 12C21.838 15.204 21.823 15.585 21.764 16.85C21.703 18.02 21.508 18.655 21.343 19.077C21.119 19.639 20.864 20.037 20.444 20.459C20.025 20.878 19.62 21.138 19.064 21.355C18.644 21.519 17.999 21.715 16.829 21.768C15.555 21.825 15.18 21.838 11.97 21.838C8.759 21.838 8.384 21.823 7.111 21.764C5.94 21.703 5.295 21.508 4.875 21.343C4.306 21.119 3.915 20.864 3.496 20.444C3.075 20.025 2.806 19.62 2.596 19.064C2.431 18.644 2.237 17.999 2.176 16.829C2.131 15.569 2.115 15.18 2.115 11.985C2.115 8.789 2.131 8.399 2.176 7.124C2.237 5.954 2.431 5.31 2.596 4.89C2.806 4.32 3.075 3.93 3.496 3.509C3.915 3.09 4.306 2.82 4.875 2.611C5.295 2.445 5.926 2.25 7.096 2.19C8.371 2.145 8.746 2.13 11.955 2.13L12 2.16ZM12 5.838C8.595 5.838 5.838 8.598 5.838 12C5.838 15.405 8.598 18.162 12 18.162C15.405 18.162 18.162 15.402 18.162 12C18.162 8.595 15.402 5.838 12 5.838ZM12 16C9.79 16 8 14.21 8 12C8 9.79 9.79 8 12 8C14.21 8 16 9.79 16 12C16 14.21 14.21 16 12 16ZM19.846 5.595C19.846 6.39 19.2 7.035 18.406 7.035C17.611 7.035 16.966 6.389 16.966 5.595C16.966 4.801 17.612 4.156 18.406 4.156C19.199 4.155 19.846 4.801 19.846 5.595Z" fill="white"/>
			</g>
			<defs>
			<clipPath id="clip0_1432_8668">
			<rect width="24" height="24" fill="white"/>
			</clipPath>
			</defs>
			</svg>
		</a>
		<a href="https://github.com/mwilber/zeta-comic-generator" class="badge github" target="_blank"  rel="noopener noreferrer" aria-label="GitHub repository link">
			<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<g clip-path="url(#clip0_1432_8626)">
			<path d="M8.02742 19.0196C8.02742 19.1147 7.91613 19.1908 7.77581 19.1908C7.61613 19.205 7.50484 19.129 7.50484 19.0196C7.50484 18.9244 7.61613 18.8484 7.75645 18.8484C7.90161 18.8341 8.02742 18.9102 8.02742 19.0196ZM6.52258 18.8055C6.48871 18.9007 6.58548 19.0101 6.73065 19.0386C6.85645 19.0861 7.00161 19.0386 7.03065 18.9435C7.05968 18.8484 6.96774 18.739 6.82258 18.6962C6.69677 18.6629 6.55645 18.7104 6.52258 18.8055ZM8.66129 18.7247C8.52097 18.758 8.42419 18.8484 8.43871 18.9577C8.45323 19.0529 8.57903 19.1147 8.72419 19.0814C8.86452 19.0481 8.96129 18.9577 8.94677 18.8626C8.93226 18.7723 8.80161 18.7104 8.66129 18.7247ZM11.8452 0.5C5.13387 0.5 0 5.50799 0 12.1045C0 17.3788 3.37742 21.8921 8.20161 23.4806C8.82097 23.59 9.03871 23.2143 9.03871 22.9052C9.03871 22.6103 9.02419 20.9838 9.02419 19.985C9.02419 19.985 5.6371 20.6984 4.92581 18.5678C4.92581 18.5678 4.37419 17.1838 3.58065 16.8271C3.58065 16.8271 2.47258 16.0804 3.65806 16.0947C3.65806 16.0947 4.8629 16.1898 5.52581 17.3217C6.58548 19.1575 8.36129 18.6296 9.05323 18.3157C9.16452 17.5547 9.47903 17.0268 9.82742 16.7129C7.12258 16.4181 4.39355 16.0328 4.39355 11.4576C4.39355 10.1498 4.76129 9.49345 5.53548 8.65641C5.40968 8.34727 4.99839 7.07269 5.66129 5.42714C6.67258 5.118 9 6.71124 9 6.71124C9.96774 6.4449 11.0081 6.30698 12.0387 6.30698C13.0694 6.30698 14.1097 6.4449 15.0774 6.71124C15.0774 6.71124 17.4048 5.11325 18.4161 5.42714C19.079 7.07744 18.6677 8.34727 18.5419 8.65641C19.3161 9.49821 19.7903 10.1545 19.7903 11.4576C19.7903 16.0471 16.9403 16.4133 14.2355 16.7129C14.6806 17.0887 15.0581 17.802 15.0581 18.9197C15.0581 20.5224 15.0435 22.5057 15.0435 22.8956C15.0435 23.2048 15.2661 23.5805 15.8806 23.4711C20.7194 21.8921 24 17.3788 24 12.1045C24 5.50799 18.5565 0.5 11.8452 0.5ZM4.70323 16.9032C4.64032 16.9507 4.65484 17.0601 4.7371 17.1505C4.81452 17.2266 4.92581 17.2599 4.98871 17.198C5.05161 17.1505 5.0371 17.0411 4.95484 16.9507C4.87742 16.8746 4.76613 16.8414 4.70323 16.9032ZM4.18065 16.5179C4.14677 16.5798 4.19516 16.6559 4.29194 16.7034C4.36935 16.751 4.46613 16.7367 4.5 16.6701C4.53387 16.6083 4.48548 16.5322 4.38871 16.4847C4.29194 16.4561 4.21452 16.4704 4.18065 16.5179ZM5.74839 18.2111C5.67097 18.2729 5.7 18.4156 5.81129 18.5059C5.92258 18.6153 6.0629 18.6296 6.12581 18.5535C6.18871 18.4917 6.15968 18.349 6.0629 18.2586C5.95645 18.1492 5.81129 18.135 5.74839 18.2111ZM5.19677 17.5119C5.11935 17.5595 5.11935 17.6832 5.19677 17.7925C5.27419 17.9019 5.40484 17.9495 5.46774 17.9019C5.54516 17.8401 5.54516 17.7164 5.46774 17.6071C5.4 17.4977 5.27419 17.4501 5.19677 17.5119Z" fill="black"/>
			</g>
			<defs>
			<clipPath id="clip0_1432_8626">
			<rect width="24" height="24" fill="white"/>
			</clipPath>
			</defs>
			</svg>
		</a>
		<a href="https://greenzeta.com" class="badge greenzeta" target="_blank"  rel="noopener noreferrer" aria-label="GreenZeta website link">
			<svg id="gz-logo" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 100 100">
			<path fill="#234914" d="M54.2,100c-6.7,0-8.2-3.3-8.2-6.1s1.8-5.1,5.1-5.1,2.1.3,4.3.9c1.5.4,2.7.7,3.7.7,1.6,0,3-.6,3.9-1.9,1-1.5,1.6-3.1,1.6-4.8s-.4-3.1-1.5-3.9c-1-.9-3.3-1.3-6-1.3s-1.2,0-2.2.1c-.7,0-1.8.1-2.8.1s-1.5,0-1.9.1h-1.2c-13.5,0-20.2-8.1-20.2-24.1s4.5-25.3,13.6-35.8c-1.6-.4-3.1-1-4.3-2.1-2.1-1.6-3.1-3.9-3.1-6.7,0-4.8,3.3-8.1,9.6-9.9l1.2-.3,2.2,4.3-1.5.6c-2.8,1-4.2,2.7-4.2,4.8s.4,2.4,1.3,3c.7.6,1.9,1.2,3.4,1.2h.1c2.2-2.4,5.1-4.6,8.8-7.2,4-2.7,6.9-3.9,9.3-3.9,3.4,0,3.9,2.4,3.9,3.4,0,2.7-1.9,5.4-5.5,7.9-3.3,2.2-7.8,3.9-13.8,4.8-9.1,9.3-13.5,19.9-13.5,32.5s.9,8.7,2.8,11.1c1.8,2.2,5.2,3.4,10,3.4s1.9,0,3.9-.1c2.4-.1,4.2-.1,5.1-.1,4.5,0,7.9,1.5,10,4.5,1.9,3,3,6.4,3,10.5,0,5.8-1.5,10.5-4.5,14.1-3.3,3.4-7.5,5.2-12.6,5.2Z"/>
			</svg>
		</a>
	</div>
  </body>
</html>