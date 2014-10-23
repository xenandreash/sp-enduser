<?php

/* This file locates settings.php in the root folder,
 * which all PHP files are expected to start their 
 * execution from.
 */

error_reporting(E_ALL ^ E_NOTICE);

require_once 'utils.php';

function settings() {
	$base = dirname($_SERVER['SCRIPT_FILENAME']);
	$settings = array();
	// default values
	$settings['public-url'] = self_url();
	if (!file_exists($base.'/settings.php'))
		die('Missing '.$base.'/settings.php; edit settings.php.default and rename it');
	require $base.'/settings.php';
	$tmp = $settings;
	foreach (func_get_args() as $arg)
		$tmp = $tmp[$arg];
	return $tmp;
}

$settings = settings();

// Always use UTC timezone
date_default_timezone_set('UTC');
