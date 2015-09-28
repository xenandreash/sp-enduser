<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

if (isset($_GET['reset']) && !isset($_GET['token'])) {
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("SELECT * FROM users WHERE username = :username;");
	$statement->execute(array(':username' => $_GET['reset']));
	if (!($row = $statement->fetch(PDO::FETCH_ASSOC)))
		$error = 'That e-mail is not registered in the local database';
	else if (abs($row['reset_password_timestamp'] - time()) < 300)
		$error = 'You can only send one reset request every 15 minutes';
	if (!isset($error)) {
		$token = uniqid();
		$publictoken = hash_hmac('sha256', $row['password'], $token);
		$statement = $dbh->prepare("UPDATE users SET reset_password_token = :token, reset_password_timestamp = :timestamp WHERE username = :username;");
		$statement->execute(array(':username' => $_GET['reset'], ':token' => $token, ':timestamp' => time()));
		mail2($_GET['reset'], 'Reset password', wordwrap("Someone (hopefully you) have requested a password reset (from IP {$_SERVER['REMOTE_ADDR']}).\r\n\r\nThe token is:\r\n$publictoken \r\n\r\nDirect URL:\r\n".$settings->getPublicURL()."/?page=forgot&reset={$_GET['reset']}&token=$publictoken", 70, "\r\n"));
	}
}

if (isset($_POST['reset']) && isset($_POST['token']) && isset($_POST['password'])) {
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("SELECT * FROM users WHERE username = :username;");
	$statement->execute(array(':username' => $_POST['reset']));
	if (!($row = $statement->fetch(PDO::FETCH_ASSOC)))
		$error = 'Unknown user';
	else if ($row['reset_password_timestamp'] !== NULL && abs($row['reset_password_timestamp'] - time()) > 3600)
		$error = 'The token is only valid for one hour';
	else if ($row['reset_password_token'] === NULL || hash_hmac('sha256', $row['password'], $row['reset_password_token']) !== $_POST['token'])
		$error = 'Invalid token';
	else if ($_POST['password'] !== $_POST['password2'])
		$error = 'The passwords doesn\'t match';
	else if (!password_policy($_POST['password'], $error2))
		$error = $error2;
	if (!isset($error)) {	
		$statement = $dbh->prepare("UPDATE users SET password = :password, reset_password_token = NULL, reset_password_timestamp = NULL WHERE username = :username;");
		$statement->execute(array(':username' => $_POST['reset'], ':password' => crypt($_POST['password'])));
		$reset = true;
	}
}

require_once BASE.'/inc/smarty.php';

if ($error) $smarty->assign('error', $error);
if ($_GET['type'] != 'create' && $_POST['type'] != 'create' && $settings->getForgotText() !== null) $smarty->assign('forgot_text', $settings->getForgotText());
if ($reset) $smarty->assign('password_reset', true);

if (isset($_GET['token']) || isset($_POST['token'])) $smarty->assign('token', $_GET['token'] ?: $_POST['token']);
if (isset($_GET['type']) || isset($_POST['type'])) $smarty->assign('type', $_GET['type'] ?: $_POST['type']);
if (isset($_GET['reset']) || isset($_POST['reset'])) $smarty->assign('reset', $_GET['reset'] ?: $_POST['reset']);

$smarty->display('forgot.tpl');
