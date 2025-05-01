<?php
ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);
    require __DIR__ . '/api/includes/key.php';
    require __DIR__ . '/api/includes/db.php';

	$displayLimit = 100;

	$database = new Database();
	$db = $database->getConnection();

	$searchId = null;
	session_start();
	if(isset($_GET["search"])) {
		$searchId = $_GET["search"];
		$_SESSION["searchId"] = $searchId;
	} elseif(isset($_SESSION["searchId"])) {
		$searchId = $_SESSION["searchId"];
	}

	$showArchived = false;
	if(isset($_GET["showarchived"])) {
		// Set to true if the string value is true, otherwise false
		$showArchived = $_GET["showarchived"] === "true";
		$_SESSION["showArchived"] = $showArchived;
	} elseif(isset($_SESSION["showArchived"])) {
		$showArchived = $_SESSION["showArchived"];
	}

	if(isset($_GET["approve"])) {
		$approve = $_GET["approve"];
	} else {
		$approve = null;
	}

	if(isset($_GET["reject"])) {
		$reject = $_GET["reject"];
	} else {
		$reject = null;
	}

	if(isset($_GET["archive"])) {
		$archive = $_GET["archive"];
	} else {
		$archive = null;
	}

	if(isset($_GET["unarchive"])) {
		$unarchive = $_GET["unarchive"];
	} else {
		$unarchive = null;
	}

	if($approve) {
		$stmt = $db->prepare("UPDATE `continuity` SET `active`= 1 WHERE `id`=".$approve.";");
		// execute query
		$stmt->execute();
	}

	if($reject) {
		$stmt = $db->prepare("UPDATE `continuity` SET `active`= 0 WHERE `id`=".$reject.";");
		// execute query
		$stmt->execute();
	}

	if($archive) {
		$stmt = $db->prepare("UPDATE `continuity` SET `archive`= 1 WHERE `id`=".$archive.";");
		// execute query
		$stmt->execute();
	}
	if($unarchive) {
		$stmt = $db->prepare("UPDATE `continuity` SET `archive`= 0 WHERE `id`=".$unarchive.";");
		// execute query
		$stmt->execute();
	}

	$sqlWhere = "permalink <> \"\"";

	if($searchId) {
		$sqlWhere .= " AND permalink = \"".$searchId."\"";
	}

	$sqlWhere .= " AND archive = " . ($showArchived ? 1 : 0);

	$sql = "SELECT `continuity`.`id`, `continuity`.`description`, `continuity`.`permalink`, `continuity`.`active`, `continuity`.`archive`, `categories`.`heading` FROM `continuity` JOIN `categories` ON categories.id = continuity.categoryId WHERE ".$sqlWhere." ORDER BY timestamp DESC LIMIT :limit";
	try {
		$stmt = $db->prepare($sql);
		$stmt->bindParam(':limit', $displayLimit, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch(PDOException $e) {
		echo "ERROR: Could not execute the query. " . $e->getMessage();
		die;
	}
?>
<html>
	<head>
		<title>Admin: Continuity</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>
			body {
				font-family: sans-serif;
			}
			th, td {
				border: none;
				padding: 10px;
				margin: 5px;
				vertical-align: top;
			}
			th {
				background: #ddd;
			}
			th:last-child {
				background: #fff;
			}
			tr:nth-child(even) {
				background: #fff;
			}
			tr:nth-child(odd) {
				background: #eee;
			}
			tr.approved {
				background: #dfd;
			}
			tr:hover {
				background: #ffffdd;
			}
			/* tr.rejected {
				background: #fdd;
			} */
			input {
				display: inline-block;
				background: #fff;
				border: solid 2px #666;
				border-radius: 5px;
				color: #000;
				padding: 10px;
				margin: 5px;
			}
			.button, button, input[type="submit"] {
				display: inline-block;
				background: #f5f5ff;
    			border: solid 2px #b9b9c6;
				border-radius: 5px;
				color: #000;
				text-decoration: none;
				text-align: center;
				padding: 10px;
				margin: 5px;
				cursor: pointer;
				text-transform: uppercase;
				vertical-align: middle;
				box-shadow: -1px -1px 2px rgba(0,0,0,0.2) inset
			}
			.button:hover {
				background: #e5e5ff;
			}
			.button:active {
				background: #e5e5ff;
			}
			.button:after {
				margin-left: 5px;
			}

			/* .button.approve {
				background: #7f7;
			}
			.button.reject {
				background: #f66;
			}
			.button.archive {
				background: #422424;
    			color: #fee;
			}
			.button.unarchive {
				background: #422424;
    			color: #fee;
			} */
			.button.approve:after {
				content: "👍";
			}
			.button.reject:after {
				content: "👎";
			}
			.button.archive:after {
				content: "🗄";
			}
			.button.unarchive:after {
				content: "📂";
			}

			td.controls {
				display: flex;
				justify-content: space-between;
				align-items: center;
				flex-direction: column;
			}
			td.controls .button {
				width: 100%;
				text-align: left;
				position: relative;
				font-size: 0.9em;
			}

			td.controls .button:after {
				position: absolute;
				display: block;
				right: 5px;
				top: 50%;
				font-size: 1.25em;
				transform: translateY(-50%);
			}
			td.controls .button:last-child {
				margin-top: 5px;
			}

			td.timestamp {
				text-align: center;
			}
			td.timestamp .complete{
				color: #666;
				font-size: 0.9em;
			}
			td.timestamp .count {
				font-weight: bold;
			}
			td.timestamp .interval {
				font-size: 3em;
			}

		</style>
	</head>
	<body>
		<a href="/admin.php" class="button" style="float:right">Comics</a>
		<h1>Admin: Continuity</h1>
  		<div style="display: flex; justify-content: space-between;">
  			<form action="/admin-continuity.php" method="GET">
  				<input type="text" name="search" placeholder="permalink">
  				<input type="submit" value="Search">
  			</form>
			<form action="/admin-continuity.php" method="GET">
  				<input type="hidden" name="showarchived" value="<?php echo $showArchived ? "false" : "true";?>">
				<input type="submit" value="<?php echo $showArchived ? "Hide" : "Show";?> Archived">
  			</form>
  		</div>
		<table cellpadding="0" cellspacing="4" width="100%">
			<tr>
				<th width="150">Date</th>
				<th>Description</th>
				<th width="150"></th>
			</tr>
			<?php foreach ($rows as $row): ?>
			<tr class="<?php if($row["active"] == 1){ echo "approved"; } elseif($row["active"] == 0){ echo "rejected"; } ?>">
				<td class="timestamp">
					<!-- Days ago -->
					 <?php
					 $date1 = new DateTime($row["timestamp"]);
					 $date2 = new DateTime();
					 $interval = $date2->diff($date1);
					 ?>
					 <span class="count">
						<span class="interval">
							<?php echo $interval->format('%a'); ?>
						</span>
						<br>
						days ago
					 </span>
					 <br><br>
					 <span class="complete">
						 <?php echo $row["timestamp"]; ?>
					 </span>
				</td>
				<td>
					<a href="<?php echo (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://comicgenerator.greenzeta.com/gallery/".$row["permalink"]; ?>" target="_blank" rel="noopener noreferrer">
					<?php echo $row["description"]; ?>
					</a>
					<br><br>
					<?php echo $row["heading"]; ?>
				</td>
				<td class="controls">
					<?php if($row["active"] == 1): ?>
					<a href="/admin-continuity.php?reject=<?php echo $row["id"] ?>" class="button reject">Reject</a>
					<?php elseif($row["active"] == 0): ?>
					<a href="/admin-continuity.php?approve=<?php echo $row["id"] ?>" class="button approve">Approve</a>
					<?php endif; ?>
					<?php if($row["archive"] == 1): ?>
						<a href="/admin-continuity.php?unarchive=<?php echo $row["id"] ?>" class="button unarchive">Unarchive</a>
					<?php else: ?>
						<a href="/admin-continuity.php?archive=<?php echo $row["id"] ?>" class="button archive">Archive</a>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</body>
</html>