<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

if (isset($_GET['forgot']) && !isset($_GET['token'])) {
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("SELECT * FROM users WHERE username = :username;");
	$statement->execute(array(':username' => $_GET['forgot']));
	if (!($row = $statement->fetch()))
		$error = 'That e-mail is not registered in the local database';
	else if (abs($row['reset_password_timestamp'] - time()) < 300)
		$error = 'You can only send one reset request every 15 minutes';
	if (!isset($error)) {
		$token = uniqid();
		$publictoken = hash_hmac('sha256', $row['password'], $token);
		$statement = $dbh->prepare("UPDATE users SET reset_password_token = :token, reset_password_timestamp = :timestamp WHERE username = :username;");
		$statement->execute(array(':username' => $_GET['forgot'], ':token' => $token, ':timestamp' => time()));
		mail2($_GET['forgot'], 'Reset password', wordwrap("Someone (hopefully you) have requested a password reset (from IP {$_SERVER['REMOTE_ADDR']}).\r\n\r\nThe token is:\r\n$publictoken \r\n\r\nDirect URL:\r\n".$settings->getPublicURL()."}/?page=forgot&forgot={$_GET['forgot']}&token=$publictoken", 70, "\r\n"));
	}
}

if (isset($_POST['reset']) && isset($_POST['token']) && isset($_POST['password'])) {
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("SELECT * FROM users WHERE username = :username;");
	$statement->execute(array(':username' => $_POST['reset']));
	if (!($row = $statement->fetch()))
		$error = 'Unknown user';
	else if (abs($row['reset_password_timestamp'] - time()) > 3600)
		$error = 'The token is only valid for one hour';
	else if (hash_hmac('sha256', $row['password'], $row['reset_password_token']) !== $_POST['token'])
		$error = 'Invalid token';
	else if ($_POST['password'] !== $_POST['password2'])
		$error = 'The passwords doesn\'t match';
	if (!isset($error)) {	
		$statement = $dbh->prepare("UPDATE users SET password = :password, reset_password_timestamp = 0 WHERE username = :username;");
		$statement->execute(array(':username' => $_POST['reset'], ':password' => crypt($_POST['password'])));
		$reset = true;
	}
}

$title = 'Reset password';
require_once BASE.'/inc/header.php';
?>
		</div>
		<?php if (isset($error)) { ?>
		<div class="message pad error"><?php p($error) ?></div>
		<?php } ?>
		<div class="halfpages">
			<div class="halfpage">
				<fieldset>
					<legend>Help</legend>
					<?php
					if ($settings->getForgotText())
						echo $settings->getForgotText();
					else { ?>
					<p>
						If your user exists in the local
						database, you can reset its password
						on this page by typing your e-mail
						address in the field.
					</p>
					<?php } ?>
				</fieldset>
			</div>
			<div class="halfpage">
				<fieldset>
					<legend>Reset</legend>
					<?php if (isset($_GET['forgot']) && !isset($error)) { ?>
					<form method="post" action="?page=forgot">
						<input type="hidden" name="reset" value="<?php p($_GET['forgot']) ?>">
						<?php if (isset($_GET['token'])) { ?>
						<p>Choose a new password.</p>
						<input type="hidden" name="token" value="<?php p($_GET['token']) ?>">
						<?php } else { ?>
						<p>Enter the token you receivied in your inbox, and choose a new password.</p>
						<div>
							<label for="token">Token</label>
							<input type="text" name="token">
						</div>
						<?php } ?>
						<div>
							<label for="password">Password</label>
							<input type="password" name="password">
						</div>
						<div>
							<label for="password2">Repeat password</label>
							<input type="password" name="password2">
						</div>
						<div>
							<label></label>
							<button type="submit">Change password</button>
						</div>
					
					</form>
					<?php } else if (isset($reset)) { ?>
					<p>Your password has been reset, now <a href="?page=login">sign in</a>.</p>
					<?php } else { ?>
					<form method="get">
						<input type="hidden" name="page" value="forgot">
						<p>Enter your e-mail address and a reset request will be sent to your inbox.</p>
						<div>
							<label for="forgot">E-mail</label>
							<input type="text" name="forgot">
						</div>
						<div>
							<label></label>
							<button type="submit">Send request</button>
						</div>
					</form>
					<?php } ?>
				</fieldset>
			</div>
		</div>
<?php require_once BASE.'/inc/footer.php'; ?>
