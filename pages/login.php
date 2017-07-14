<?php
if (!defined('SP_ENDUSER')) die('File not included');

if (isset($_POST['totp_verify_key'])) {
	$session_name = $settings->getSessionName();
	if ($session_name)
		session_name($session_name);
	session_start();
	session_regenerate_id(true);

	if ($_SESSION['authenticated'] === 'totp')
	{
		$google2fa = new PragmaRX\Google2FA\Google2FA();
		$secret = Session::Get()->getSecretKey($_SESSION['username']);
		if ($secret && $google2fa->verifyKey($secret, $_POST['totp_verify_key']))
		{
			$_SESSION['authenticated'] = true;
			if ($_POST['query'])
				header("Location: ?".$_POST['query']);
			else
				header("Location: .");
			die();
		}
		$error = 'Bad two-factor token';
		session_unset();
		session_destroy();
	}
}

if (isset($_POST['username']) && isset($_POST['password'])) {
	$session_name = $settings->getSessionName();
	if ($session_name)
		session_name($session_name);
	session_start();
	session_regenerate_id(true);

	$_SESSION['timezone'] = $_POST['timezone'];
	$_SESSION['useiframe'] = $_POST['useiframe'];
	$username = $_POST['username'];
	$password = $_POST['password'];

	foreach ($settings->getAuthSources() as $method)
	{
		$authmethod = 'halon_login_' . $method['type'];
		if (!function_exists($authmethod))
			require_once 'modules/Login.'.$method['type'].'.php';
		$result = $authmethod($username, $password, $method, $settings);
		if ($result && is_array($result))
		{
			$_SESSION = array_merge($_SESSION, $result);

			if ($settings->getTwoFactorAuth() && Session::Get()->getSecretKey($_SESSION['username'])) 
				$_SESSION['authenticated'] = 'totp';
			else
				$_SESSION['authenticated'] = true;

			break;
		}
	}

	if (isset($_SESSION['authenticated']))
	{
		if ($_SESSION['authenticated'] === true)
		{
			if ($_POST['query'])
				header("Location: ?".$_POST['query']);
			else
				header("Location: .");
			die();
		}
	}
	else
	{
		$error = 'Login failed';
		session_unset();
		session_destroy();
	}
}

require_once BASE.'/inc/smarty.php';

$smarty->assign('totp', $_SESSION['authenticated'] === 'totp');

if ($settings->getLoginText() !== null) $smarty->assign('login_text', $settings->getLoginText());
if ($error) $smarty->assign('error', $error);
if (has_auth_database()) $smarty->assign('forgot_password', true);
if ($_GET['query']) $smarty->assign('query', $_GET['query']);

$smarty->display('login.tpl');
