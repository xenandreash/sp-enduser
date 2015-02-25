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
		mail2($_GET['forgot'], 'Reset password', wordwrap("Someone (hopefully you) have requested a password reset (from IP {$_SERVER['REMOTE_ADDR']}).\r\n\r\nThe token is:\r\n$publictoken \r\n\r\nDirect URL:\r\n".$settings->getPublicURL()."/?page=forgot&forgot={$_GET['forgot']}&token=$publictoken", 70, "\r\n"));
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
	else if (!password_policy($_POST['password'], $error2))
		$error = $error2;
	if (!isset($error)) {	
		$statement = $dbh->prepare("UPDATE users SET password = :password, reset_password_timestamp = 0 WHERE username = :username;");
		$statement->execute(array(':username' => $_POST['reset'], ':password' => crypt($_POST['password'])));
		$reset = true;
	}
}

$title = 'Reset password';
require_once BASE.'/partials/header.php';
?>
	<div class="container">
		<div class="col-md-offset-3 col-md-6">
			<div class="panel panel-default" style="margin-top:40px;">
				<div class="panel-heading">
					<h3 class="panel-title">Forgot password</h3>
				</div>
				<div class="panel-body">
					<?php if (isset($error)) { ?>
					<div class="alert alert-danger"><?php p($error) ?></div>
					<?php } ?>
					
					<?php if ($settings->getForgotText() !== null) { ?>
					<p>
						<?php p($settings->getForgotText()); ?>
						<hr />
					</p>
					<?php } ?>
					
					<?php if (isset($_GET['forgot']) && !isset($error)) { ?>
					<form class="form-horizontal" method="post" action="?page=forgot">
						<input type="hidden" name="reset" value="<?php p($_GET['forgot']) ?>">
						<?php if (isset($_GET['token'])) { ?>
							<p>Choose a new password.</p>
							<input type="hidden" name="token" value="<?php p($_GET['token']) ?>">
						<?php } else { ?>
							<p class="alert alert-success">Enter the token you receivied in your inbox, and choose a new password.</p>
							<div class="form-group">
								<label class="control-label col-sm-3" for="token">Token</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="token" autofocus value="<?php //print $publictoken; ?>">
								</div>
							</div>
						<?php } ?>
						<div class="form-group">
							<label class="control-label col-sm-3" for="password">Password</label>
							<div class="col-sm-9">
								<input type="password" class="form-control" name="password" autofocus>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-sm-3" for="password2">Repeat password</label>
							<div class="col-sm-9">
								<input type="password" class="form-control" name="password2">
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<button type="submit" class="btn btn-primary">Change password</button>
							</div>
						</div>
					</form>
					<?php } else if (isset($reset)) { ?>
						<p class="alert alert-success">Your password has been reset!</p>
						<div class="col-sm-offset-3 col-sm-9">
							<a class="btn btn-primary" href="?page=login">Sign in</a>
						</div>
					<?php } else { ?>
					<form class="form-horizontal" method="get">
						<input type="hidden" name="page" value="forgot">
						<div class="form-group">
							<label class="control-label col-sm-3" for="forgot">E-mail</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="forgot" autofocus>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<button type="submit" class="btn btn-primary">Reset password</button>
								<a class="btn btn-default" href=".">I remembered!</a>
							</div>
						</div>
					</form>
					<?php } ?>
				</div>
			</div>
		</div>
<?php require_once BASE.'/partials/footer.php'; ?>
