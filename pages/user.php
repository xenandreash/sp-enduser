<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

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
	$row = $statement->fetch();
	
	if (!$row || $row['password'] !== crypt($_POST['old_password'], $row['password'])) {
		$error = "Your old password is incorrect!";
		return;
	}
	
	$statement = $dbh->prepare("UPDATE users SET password = :password WHERE username = :username;");
	$statement->execute(array(':username' => Session::Get()->getUsername(), ':password' => crypt($_POST['password'])));
	$changedPassword = true;
}

$source = Session::Get()->getSource();
$changedPassword = false;
$error = NULL;
if ($source == 'database' && isset($_POST['password']))
	do_change_password();

$access = Session::Get()->getAccess();
$access_mail = (is_array($access['mail']) ? $access['mail'] : array());
$access_domain = (is_array($access['domain']) ? $access['domain'] : array());

$title = 'Account';
require_once BASE.'/partials/header.php';
?>
		<div class="container">
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Permissions</h3>
					</div>
					<div class="panel-body">
						<?php if (empty($access_mail) && empty($access_domain)) { ?>
						<p>
							You have no restrictions, you may view messages to/from any domain.
						</p>
						<?php } else { ?>
						<p>
							You are authorized to view messages sent from/to the following <?php if (!empty($access_domain)) { p('domains'); } else { p('users'); } ?>:
						</p>
						<ul>
							<?php
							foreach (!empty($access_domain)?$access_domain:$access_mail as $a) {
								echo "<li>";
								p($a);
								echo "</li>";
							}
							?>
						</ul>
							<?php if (!empty($access_domain) && !empty($access_mail)) { ?>
							<p>
								And the following users:
							</p>
							<ul>
								<?php
								foreach ($access_mail as $mail) {
									echo "<li>";
									p($mail);
									echo "</li>";
								}
								?>
							</ul>
							<?php } ?>
						<?php } ?>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Change password</h3>
					</div>
					<div class="panel-body">
						<?php if ($changedPassword) { ?>
						<div class="alert alert-success">Password changed</div>
						<?php } ?>
						<?php if ($error) { ?>
						<div class="alert alert-danger"><?php p($error); ?></div>
						<?php } ?>
						
						<?php if ($source == 'database') { ?>
							<form class="form-horizontal" method="post" action="?page=user">
								<div class="form-group">
									<label class="col-sm-3 control-label" for="old_password">Old Password</label>
									<div class="col-sm-9">
										<input type="password" class="form-control" name="old_password" id="old_password" placeholder="Your old password">
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label" for="password">New Password</label>
									<div class="col-sm-9">
										<input type="password" class="form-control" name="password" id="password" placeholder="Your new password">
										<input type="password" class="form-control" name="password2" id="password2" placeholder="And again, just to be sure">
									</div>
								</div>
								<div class="form-group">
									<div class="col-sm-offset-2 col-sm-10">
										<button type="submit" class="btn btn-primary">Change</button>
									</div>
								</div>
							</form>
						<?php } else { ?>
							<p>
								You are authenticated externally, so password changes are not done here.
							</p>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
<?php require_once BASE.'/partials/footer.php'; ?>
