<?php
if (!defined('SP_ENDUSER')) die('File not included');

$title = 'Install';
require_once 'inc/header.php';
require_once 'inc/core.php';

$ok = true;
?>
	</div>
	<div style="padding: 10px;"> 
<?php
if (empty($settings['node'])) {
	$ok = false;
?>
	<p><strong>ERROR:</strong> No system node(s)</p>
<?php } ?>
<?php
if (!in_array('curl', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> cURL extension is missing. Without it, the UI will run slower (not being able to run searches in parallel).</p>
<?php
}
if (!isset($settings['api-key'])) {
?>
	<p><em>WARNING:</em> No api-key, dynamic user creation and black/whitelist lookups will not work until you specify one.</p>
<?php } else { ?>
	<p><em>INFO:</em> The trigger URL for this setup is <tt><?php p(self_url()); ?>api.php?api-key=<i>secret-api-key</i></tt>.</p>
<?php } ?>
<?php
if (empty($settings['authentication'])) {
	$ok = false;
?>
	<p><strong>ERROR:</strong> No authentication sources</p>
<?php } ?>
<?php
if (isset($settings['database']['dsn'])) {
?>
	<p>
<?php
	$notes = array();
	try {
		$dbh = new PDO($settings['database']['dsn'], $settings['database']['user'], $settings['database']['password']);
		$statement = $dbh->prepare('SELECT * FROM users LIMIT 1;');
		if (!$statement || $statement->execute() === false) {
			$notes[] = 'Adding table users';
			$dbh->exec('CREATE TABLE users (username VARCHAR(128), password TEXT, reset_password_token TEXT, reset_password_timestamp INTEGER, PRIMARY KEY(username));');
		}
		$statement = $dbh->prepare('SELECT * FROM users_relations LIMIT 1;');
		if (!$statement || $statement->execute() === false) {
			$notes[] = 'Adding table users_relations';
			$dbh->exec('CREATE TABLE users_relations (username VARCHAR(128) REFERENCES users(username) ON DELETE CASCADE, type VARCHAR(32), access VARCHAR(128), PRIMARY KEY(username, type, access));');
		}
		$statement = $dbh->prepare('SELECT * FROM bwlist LIMIT 1;');
		if (!$statement || $statement->execute() === false) {
			$notes[] = 'Adding table bwlist';
			$dbh->exec('CREATE TABLE bwlist (access VARCHAR(128), type VARCHAR(32), value VARCHAR(128), PRIMARY KEY(access, type, value));');
		}
		if (!empty($notes))
			echo 'Database<ul><li>'.implode($notes, '<li>').'</ul>';
	} catch (PDOException $e) {
		$ok = false;
		echo "<p><b>Database error: ".$e->getMessage()."</b></p>";
	}
} else {
	echo "<em>WARNING:</em> No database. Database users and black/whitelist will not be available until created.";
}
?>
	</p>
<?php if ($ok) { ?>
	<p>
		<strong>You should now remove install.php to proceed.</strong>
	</p>
<?php } else { ?>
	<p>
		<strong>System configuration is incomplete. Edit settings.php</strong>
	</p>
<?php } ?>
	</div>
<?php
require_once 'inc/footer.php';
?>
