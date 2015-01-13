<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

$source = Session::Get()->getSource();
$changedPassword = false;
if ($source == 'database' && isset($_POST['password']) && $_POST['password'] == $_POST['password2']) {
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("UPDATE users SET password = :password WHERE username = :username;");
	$statement->execute(array(':username' => Session::Get()->getUsername(), ':password' => crypt($_POST['password'])));
	$changedPassword = true;
}

$access = Session::Get()->getAccess();
$access_mail = (is_array($access['mail']) ? $access['mail'] : array());
$access_domain = (is_array($access['domain']) ? $access['domain'] : array());

$title = 'Account';
require_once BASE.'/partials/header.php';
?>
		<?php if ($changedPassword) { ?>
		<div class="alert alert-success">Password changed</div>
		<?php } ?>
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
						<?php } ?>
						<?php if (!empty($access_mail)) { ?>
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
						<?php if (!empty($access_domain)) { ?>
							<?php if (!empty($access_mail)) { ?>
							<p>
								And the following users:
							</p>
							<?php } ?>
							<ul>
								<?php
								foreach ($access_domain as $domain) {
									echo "<li>";
									p($domain);
									echo "</li>";
								}
								?>
							</ul>
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
						<?php if ($source == 'database') { ?>
							<form method="post" action="?page=user">
								<div>
									<label>Password</label>
									<input type="password" name="password">
								</div>
								<div>
									<label>Repeat password</label>
									<input type="password" name="password2">
								</div>
								<div>
									<label></label>
									<button type="submit">Change</button>
								</div>
							</form>
						<?php } else { ?>
							<p>
								User authenticated using <?php p($source); ?> and can not change the password from this page.
							</p>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
<?php require_once BASE.'/partials/footer.php'; ?>
