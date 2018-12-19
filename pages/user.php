<?php
if (!defined('SP_ENDUSER')) die('File not included');

function do_change_password()
{
	global $settings, $error, $changedPassword;
	
	if ($_POST['password'] != $_POST['password2']) {
		$error = "Your new passwords don't match!";
		return;
	}
	if (!password_policy($_POST['password'], $error2)) {
		$error = $error2;
		return;
	}
	
	$dbh = $settings->getDatabase();
	
	$statement = $dbh->prepare("SELECT * FROM users WHERE username = :username;");
	$statement->execute(array(':username' => Session::Get()->getUsername()));
	$row = $statement->fetch(PDO::FETCH_ASSOC);
	
	if (!$row || !password_verify($_POST['old_password'], $row['password'])) {
		$error = "Your old password is incorrect!";
		return;
	}
	
	$statement = $dbh->prepare("UPDATE users SET password = :password WHERE username = :username;");
	$statement->execute(array(':username' => Session::Get()->getUsername(), ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT)));
	$changedPassword = true;
}

function generateQRSvg($google2fa, $issuer, $user, $secret)
{
	$url = $google2fa->getQRCodeUrl($issuer, $user, $secret);

	$renderer = new BaconQrCode\Renderer\Image\Svg();
	$renderer->setWidth(256);
	$renderer->setHeight(256);
	$renderer->setBackgroundColor(new BaconQrCode\Renderer\Color\Rgb(255,255,255));

	$writer = new BaconQrCode\Writer($renderer);

	return $writer->writeString($url, "utf-8");
}

$changedPassword = false;
$error = NULL;
if (Session::Get()->getSource() == 'database' && isset($_POST['password']))
	do_change_password();

require_once BASE.'/inc/smarty.php';

if ($settings->getTwoFactorAuth())
{
	if (Session::Get()->getSecretKey(Session::Get()->getUsername()))
		$totp_enabled = true;
	else
		$totp_enabled = false;

	$google2fa = new PragmaRX\Google2FA\Google2FA();
	$smarty->assign('totp_enabled', $totp_enabled);

	if (!$totp_enabled && isset($_GET['totp_enable'])) {
		if (isset($_POST['totp_secret']) && isset($_POST['totp_verify_key'])) {
			if ($google2fa->verifyKey($_POST['totp_secret'], $_POST['totp_verify_key'])) {
				$dbh = $settings->getDatabase();
				$statement = $dbh->prepare("INSERT INTO users_totp (username, secret) VALUES (:username, :secret);");
				if ($statement->execute([':username' => Session::Get()->getUsername(), ':secret' => $_POST['totp_secret']])) {
					$smarty->assign('totp_success', true);
					$smarty->assign('totp_enabled', true);
				} else {
					$smarty->assign('totp_error', true);
				}
			} else {
				$smarty->assign('totp_error', true);
				$smarty->assign('totp_secret', $_POST['totp_secret']);
				$smarty->assign('totp_qr_svg', generateQRSvg($google2fa, $settings->getPageName(), Session::Get()->getUsername(), $_POST['totp_secret']));
			}
		} else {
			$secret = $google2fa->generateSecretKey();

			$smarty->assign('totp_secret', $secret);
			$smarty->assign('totp_qr_svg', generateQRSvg($google2fa, $settings->getPageName(), Session::Get()->getUsername(), $secret));
		}
		$smarty->assign('totp_enable', true);
	} else if ($totp_enabled && isset($_GET['totp_disable'])) {
		if (isset($_POST['totp_verify_key'])) {
			if ($google2fa->verifyKey(Session::Get()->getSecretKey(Session::Get()->getUsername()), $_POST['totp_verify_key'])) {
				$dbh = $settings->getDatabase();
				$statement = $dbh->prepare("DELETE FROM users_totp WHERE username = :username;");
				$statement->execute([':username' => Session::Get()->getUsername()]);
				$smarty->assign('totp_disable_success', true);
				$smarty->assign('totp_enabled', false);
			} else {
				$smarty->assign('totp_error', true);
			}
		}
		$smarty->assign('totp_disable', true);
	}
}

$smarty->assign('settings_totp_enabled', $settings->getTwoFactorAuth());

if (is_array($access['mail'])) $smarty->assign('access_mail', $access['mail']);
if (is_array($access['domain'])) $smarty->assign('access_domain', $access['domain']);
if (is_array($access['sasl'])) $smarty->assign('access_sasl', $access['sasl']);
if ($changedPassword) $smarty->assign('password_changed', true);
if (Session::Get()->getSource() == 'database') $smarty->assign('password_changeable', true);
if ($error) $smarty->assign('error', $error);

$smarty->display('user.tpl');
