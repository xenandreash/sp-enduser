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

$settings = Settings::Get();

// Always use UTC timezone
date_default_timezone_set('UTC');
