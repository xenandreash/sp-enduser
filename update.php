<?php

if (!isset($_SERVER['argc']))
    die('This file can only be run from command line');

define('UPDATE_IGUARD', true);
define('BASE', dirname(__FILE__));

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

if ($version['update_required']) {
    $a = getAvailableVersion();
    for ($v = getCurrentVersion()+1; $v <= $a; $v++) {
        require_once('/updates/'.$v.'.php');
        if ($v != getCurrentVersion())
            die("Error - Unable to update database to version ".$v."\n");
    }
} else {
    die("Already up-to-date.\n");
}
