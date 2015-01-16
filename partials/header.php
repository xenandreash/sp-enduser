<?php

// XXX this file is included from login.php as well

require_once BASE.'/inc/core.php';

$pagename = $settings->getPageName();
$title = $title ?: 'Untitled';
$logo = file_exists('template/logo.png') ? 'template/logo.png' : 'static/img/logo.png';
$styles = file_exists('template/styles.css') ? 'template/styles.css' : 'static/css/styles.css';

function header_active($page) {
	if ($_GET['page'] == $page || ($_GET['page'] == '' && $page == 'index'))
		echo ' active';
}

$dbCredentials = $settings->getDBCredentials();

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
		<title><?php echo $title ?> | <?php echo $pagename ?></title>
		<link rel="stylesheet" href="static/css/bootstrap.min.css">
		<link rel="stylesheet" href="<?php echo $styles ?>">
		<script src="static/js/jquery.min.js"></script>
		<?php if (isset($javascript)) foreach ($javascript as $js) { ?>
		<script src="<?php echo $js; ?>"></script>
		<?php } ?>
		<script src="static/js/bootstrap.min.js"></script>
	</head>
	<body class="<?php p($body_class); ?>">
		<?php if (Session::Get()->getUsername()) { ?>
		<nav class="navbar navbar-inverse navbar-static-top">
			<div class="container-fluid">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<?php if (!$show_back) { ?>
					<a class="navbar-brand visible-xs" data-toggle="collapse" data-target="#navbar-collapse"><?php p($title); ?></a>
					<?php } else { ?>
					<a class="navbar-brand visible-xs" href="javascript:history.go(-<?php echo $back_steps ?: 1; ?>);">&larr;&nbsp;Back</a>
					<?php } ?>
				</div>
				<div class="collapse navbar-collapse" id="navbar-collapse">
					<ul class="nav navbar-nav">
						<li class="mail<?php header_active('index'); ?>"><a href="."><i class="glyphicon glyphicon-envelope"></i>&nbsp;Messages</a></li>
						<?php if ($dbCredentials['dsn'] && $settings->getDisplayBWList()) { ?>
						<li class="bwlist<?php header_active('bwlist'); ?>"><a href="?page=bwlist"><i class="glyphicon glyphicon-inbox"></i>&nbsp;Black/whitelist</a></li>
						<?php } ?>
					</ul>
					<ul class="nav navbar-nav navbar-right">
						<li class="user<?php header_active('user') ?>"><a href="?page=user"><i class="glyphicon glyphicon-user"></i>&nbsp;<?php p(Session::Get()->getUsername()); ?></a></li>
						<li class="logout<?php header_active('logout') ?>"><a href="?page=logout">Logout</a></li>
					</ul>
				</div>
			</div>
		</nav>
		<!--<div id="nav">
			<ul id="menu">
				<li class="mail<?php header_active('index') ?>"><a href=".">Messages</a></li>
				<?php if ($dbCredentials['dsn'] && $settings->getDisplayBWList()) { ?>
				<li class="bwlist<?php header_active('bwlist') ?>"><a href="?page=bwlist">Black/whitelist</a></li>
				<?php } ?>
			</ul>
			<ul id="taskbar">
				<li class="user<?php header_active('user') ?>"><a href="?page=user"><?php echo htmlspecialchars(Session::Get()->getUsername()) ?></a></li>
				<?php if (Session::Get()->getSource() != 'cpanel') { ?>
				<li class="logout"><a href="?page=logout">Logout</a></li>
				<?php } ?>
			</ul>
		</div>-->
		<?php } ?>
		<!-- <div id="header">
			<h1><?php echo $title ?></h1>
			<img src="<?php echo $logo ?>" id="logo"> -->
