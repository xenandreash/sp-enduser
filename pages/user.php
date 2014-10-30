<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

$source = Session::Get()->getSource();
$changedPassword = false;
if ($source == 'database' && isset($_POST['password']) && $_POST['password'] == $_POST['password2']) {
	$dbh = new Database();
	$statement = $dbh->prepare("UPDATE users SET password = :password WHERE username = :username;");
	$statement->execute(array(':username' => Session::Get()->getUsername(), ':password' => crypt($_POST['password'])));
	$changedPassword = true;
}

$title = 'Account';
require_once BASE.'/inc/header.php';
?>
		</div>
		<?php if ($changedPassword) { ?>
		<div class="message pad ok">Password changed</div>
		<?php } ?>
		<div class="halfpages">
			<div class="halfpage">
				<fieldset>
					<legend>Permissions</legend>
					<p>You are authorized to view messages sent from/to the following users/domains:</p>
					<ul>
					<?php
						$r = 0;
						$access = Session::Get()->getAccess();
						if (is_array($access['mail'])) { ?>
							<?php
							foreach ($access['mail'] as $mail) {
								++$r;
								echo "<li>";
								p($mail);
							}
						}
						if (is_array($access['domain'])) { ?>
							<?php
							foreach ($access['domain'] as $domain) {
								++$r;
								echo "<li>";
								p($domain);
							}
						}
						if ($r == 0)
							echo "<li>No restrictions (you can view everything)";
					?>
					</ul>
				</fieldset>
			</div>
			<div class="halfpage">
				<fieldset>
					<legend>Change password</legend>
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
				</p>
			<?php } else { ?>
				<p>
					User authenticated using <?php p($source); ?> and can not change the password from this page.
				</p>
			<?php } ?>
			</fieldset>
			</div>
		</div>
<?php require_once BASE.'/inc/footer.php'; ?>
