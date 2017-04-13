<?php

if (!isset($_SERVER['argc']))
    die('This file can only be run from command line');

define('UPDATE_IGUARD', true);
define('BASE', dirname(__FILE__));

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

$dbh = $settings->getDatabase();

$updateSkip = null;

switch (@$argv[1]) {
    case '--skip': 
        $updateSkip = $argv[2];
        break;
    case 'check':
        exit($version['update_required'] ? 0 : 1);
}

if ($version['update_required']) {
    $a = getAvailableVersion();
    for ($v = getCurrentVersion()+1; $v <= $a; $v++) {
        if ($updateSkip != $v) {
            echo "Running [$v.php] - Updating from version ".getCurrentVersion()." to $a\n";
            $errCode = (require_once BASE.'/updates/'.$v.'.php');
            if ($errCode)
                die("Error - Unable to apply update (Return code: $errCode)\n");
        } else {
            echo "Skipping [$v.php]... \n";
        }
        $dbh->exec('UPDATE dbversion SET current = '.$v.';');
        if ($v != getCurrentVersion())
            die("Error - Unable to update database to version $v\n");
    }
    echo "Update completed.\n";
} else {
    die("Already up-to-date.\n");
}
