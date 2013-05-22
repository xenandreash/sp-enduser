<?php

// XXX this file is included from login.php as well

require_once('core.php');

$pagename = $settings['pagename'] ?: 'Halon SP for end-users';
$title = $title ?: 'Untitled';
$logo = file_exists('template/logo.png') ? 'template/logo.png' : 'img/logo.png';
$styles = file_exists('template/styles.css') ? 'template/styles.css' : 'inc/styles.css';

function header_active($file) {
	if (strpos($_SERVER['SCRIPT_FILENAME'], $file) !== false)
		echo ' active';
}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title><?php echo $title ?> | <?php echo $pagename ?></title>
		<link rel="stylesheet" href="<?php echo $styles ?>">
		<script src="inc/jquery-1.9.1.min.js"></script>
		<?php if (isset($javascript)) foreach($javascript as $js) { ?>
		<script src="<?php echo $js; ?>"></script>
		<?php } ?>
	</head>
	<body>
		<?php if (isset($_SESSION['username'])) { ?>
		<div id="nav">
			<ul id="menu">
				<li class="mail<?php header_active('/index.php') ?>"><a href="index.php">Messages</a></li>
				<?php if (settings('database')) { ?>
				<li class="bwlist<?php header_active('/bwlist.php') ?>"><a href="bwlist.php">Black/whitelist</a></li>
				<?php } ?>
			</ul>
			<ul id="taskbar">
				<li class="user<?php header_active('/user.php') ?>"><a href="user.php"><?php echo htmlspecialchars($_SESSION['username']) ?></a></li>
				<li class="logout"><a href="logout.php">Logout</a></li>
			</ul>
		</div>
		<?php } ?>
		<div id="header">
			<h1><?php echo $title ?></h1>
			<img src="<?php echo $logo ?>" id="logo">
