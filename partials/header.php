<?php

// XXX this file is included from login.php as well

require_once BASE.'/inc/core.php';

$pagename = $settings->getPageName();
$title = $title ?: 'Untitled';
$logo = file_exists('template/logo.png') ? 'template/logo.png' : 'static/img/logo.png';
$styles = file_exists('template/styles.css') ? 'template/styles.css' : 'static/styles.css';

function header_active($page) {
	if ($_GET['page'] == $page || ($_GET['page'] == '' && $page == 'index'))
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
		<script src="static/jquery-1.9.1.min.js"></script>
		<?php if (isset($javascript)) foreach ($javascript as $js) { ?>
		<script src="<?php echo $js; ?>"></script>
		<?php } ?>
	</head>
	<body>
		<?php if (class_exists('Session')) { ?>
		<div id="nav">
			<ul id="menu">
				<li class="mail<?php header_active('index') ?>"><a href="?page=index">Messages</a></li>
				<?php if ($settings->getDBCredentials()['dsn']) { ?>
				<li class="bwlist<?php header_active('bwlist') ?>"><a href="?page=bwlist">Black/whitelist</a></li>
				<?php } ?>
			</ul>
			<ul id="taskbar">
				<li class="user<?php header_active('user') ?>"><a href="?page=user"><?php echo htmlspecialchars(Session::Get()->getUsername()) ?></a></li>
				<?php if (Session::Get()->getSource() != 'cpanel') { ?>
				<li class="logout"><a href="?page=logout">Logout</a></li>
				<?php } ?>
			</ul>
		</div>
		<?php } ?>
		<div id="header">
			<h1><?php echo $title ?></h1>
			<img src="<?php echo $logo ?>" id="logo">
