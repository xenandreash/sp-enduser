<?php
if (!defined('SP_ENDUSER')) die('File not included');

/*function getSecretKey($username) {
	global $settings;
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("SELECT * FROM users_totp WHERE username = :username;");
	$statement->execute([':username' => $username]);
	$row = $statement->fetch(PDO::FETCH_ASSOC);

	if (!$row)
		return;
	else
		return $row['secret'];
}*/

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

	if ($settings->getTwoFactorAuth()) 
	{
		$totp_authed = false;
		$totp_enabled = false;
		
		$google2fa = new PragmaRX\Google2FA\Google2FA();
		$secret = Session::Get()->getSecretKey($username);

		if ($secret)
			$totp_enabled = true;

		if (isset($_POST['totp_verify_key']) && $totp_enabled && $google2fa->verifyKey($secret, $_POST['totp_verify_key']))
			$totp_authed = true;
	}

	if (!$settings->getTwoFactorAuth() || !$totp_enabled || $totp_authed)
	{
		foreach ($settings->getAuthSources() as $method)
		{
			$authmethod = 'halon_login_' . $method['type'];
			if (!function_exists($authmethod))
				require_once 'modules/Login.'.$method['type'].'.php';
			$result = $authmethod($username, $password, $method, $settings);
			if ($result && is_array($result))
			{
				$_SESSION = array_merge($_SESSION, $result);
				break;
			}
		}
		if (isset($_SESSION['username'])) {
			if ($_POST['query'])
				header("Location: ?".$_POST['query']);
			else
				header("Location: .");
			die();
		}
	}

	$error = 'Login failed';
	session_destroy();
}

require_once BASE.'/inc/smarty.php';

$smarty->assign('settings_totp_enabled', $settings->getTwoFactorAuth());

if ($settings->getLoginText() !== null) $smarty->assign('login_text', $settings->getLoginText());
if ($error) $smarty->assign('error', $error);
if (has_auth_database()) $smarty->assign('forgot_password', true);
if ($_GET['query']) $smarty->assign('query', $_GET['query']);

$smarty->display('login.tpl');
