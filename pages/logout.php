<?php
if(!defined('SP_ENDUSER')) die('File not included');

session_start();
session_destroy();
header("Location: index.php");
die();

?>
