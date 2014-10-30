<?php

/* This file locates settings.php in the root folder,
 * which all PHP files are expected to start their 
 * execution from.
 */

error_reporting(E_ALL ^ E_NOTICE);

require_once BASE.'/inc/utils.php';

function halon_autoloader($class) {
	require BASE.'/classes/'.$class.'.class.php';
}
spl_autoload_register('halon_autoloader');

function set_default(&$settings, $key, $value) {
	// Assign a default value if the value is absent, or if it's an empty array
	if(!isset($settings[$key]) || (is_array($settings[$key]) && empty($settings[$key])))
		$settings[$key] = $value;
}

function settings() {
	$settings = array();
	
	if (!file_exists(BASE.'/settings.php'))
		die('Missing '.BASE.'/settings.php; edit settings-default.php and rename it');
	require BASE.'/settings.php';
	
	// default values
	set_default($settings, 'public-url', self_url());
	set_default($settings, 'authentication', array( array( 'type' => 'server' ) ));
	
	$tmp = $settings;
	foreach (func_get_args() as $arg)
		$tmp = $tmp[$arg];
	
	return $tmp;
}

$settings = settings();

// Always use UTC timezone
date_default_timezone_set('UTC');
