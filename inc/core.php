<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once BASE.'/inc/utils.php';

// Always use UTC timezone
date_default_timezone_set('UTC');

// Autoload our classes from /classes/
function halon_autoloader($class) {
	require BASE.'/classes/'.$class.'.class.php';
}
spl_autoload_register('halon_autoloader');

// Autoload Composer packages from /vendor/
require_once BASE.'/vendor/autoload.php';

// Conveniently access the Settings instance as $settings
$settings = Settings::Get();
