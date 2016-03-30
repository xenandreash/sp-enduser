<?php
if (!defined('SP_ENDUSER')) die('File not included');

if (!file_exists(BASE.'/settings.php'))
	die('Missing '.BASE.'/settings.php; edit <b>settings-default.php</b> and rename it to <b>settings.php</b>.');

$title = 'Install';
require_once BASE.'/inc/core.php';
?>
<!DOCTYPE html>
<html>
<head>
	<title>Install</title>
</head>
<body>
<?php
$ok = true;
?>
	<div style="padding: 10px;"> 
<?php
if (count($settings->getNodes()) == 0) {
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
if (!in_array('openssl', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> OpenSSL extension is missing. Without it, generated passwords will be insecure!</p>
<?php
}
if (!in_array('dom', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> DOM extension is missing. Without it, email previews may be incomplete. <em>(This usually means you're running on CentOS, and need to install the <code>php-xml</code> package.)</em></p>
<?php
}
if (!in_array('hash', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> HASH extension is missing. Without it, we are unable to securily store passwords. <em>(This usually means you're running on CentOS, and need to install the <code>php-hash</code> package.)</em></p>
<?php
}
if (!in_array('gettext', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> GETTEXT extension is missing. Without it, you will be unable to get correct character encodings. <em>(This usually means you're running on CentOS, and need to install the <code>php-gettext</code> package.)</em></p>
<?php
}
if (!in_array('session', get_loaded_extensions())) {
# Soap requires session
?>
	<p><em>WARNING:</em> SESSION extension is missing. Without it, you will be unable to keep track of the users supplied credentials. <em>(This usually means you're running on CentOS, and need to install the <code>php-session</code> package.)</em></p>
<?php
}
if (!in_array('soap', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> SOAP extension is missing. Without it, you will be unable to connect directly to nodes. <em>(This usually means you're running on CentOS, and need to install the <code>php-soap</code> package.)</em></p>
<?php
}
if (!in_array('ldap', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> LDAP extension is missing. Without it, you will not be able to use LDAP for authentication.</p>
<?php
}
if (!in_array('rrd', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> RRD extension is missing. Without it, you will be unable to show graphs.</p>
<?php
}
if ($settings->getAPIKey() === null) {
?>
	<p><em>WARNING:</em> No api-key, dynamic user creation and black/whitelist lookups will not work until you specify one.</p>
<?php } else { ?>
	<p><em>INFO:</em> The API URL for this setup is: <code><?php echo htmlspecialchars($settings->getPublicURL()); ?>api.php?api-key=<i>secret-api-key</i></code></p>
<?php } ?>
<?php
if (count($settings->getAuthSources()) == 0) {
	$ok = false;
?>
	<p><strong>ERROR:</strong> No authentication sources</p>
<?php } ?>
<?php
$dbCredentials = $settings->getDBCredentials();

function createMessageLog(&$dbh, &$notes, $name)
{
	global $settings;
	$useridtype = $settings->getPartitionType() == 'string' ? 'VARCHAR(256)' : 'BIGINT';
	$notes[] = 'Adding table '.$name;
	$dbh->exec('CREATE TABLE '.$name.' (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, userid '.$useridtype.' DEFAULT NULL, owner VARCHAR(300), owner_domain VARCHAR(300), msgts0 TIMESTAMP DEFAULT CURRENT_TIMESTAMP, msgts INT, msgid VARCHAR(100), msgactionid INT, msgaction VARCHAR(50), msglistener VARCHAR(100), msgtransport VARCHAR(100), msgsasl VARCHAR(300), msgfromserver VARCHAR(300), msgfrom VARCHAR(300), msgfrom_domain VARCHAR(300), msgto VARCHAR(300), msgto_domain VARCHAR(300), msgsubject TEXT, score_rpd NUMERIC(10,5), score_sa NUMERIC(10,5), scores TEXT, msgdescription TEXT, serialno VARCHAR(100));');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgid               ON '.$name.'(msgid);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_userid              ON '.$name.'(userid);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_owner               ON '.$name.'(owner);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_owner_domain        ON '.$name.'(owner_domain);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgfromserver       ON '.$name.'(msgfromserver);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgfrom             ON '.$name.'(msgfrom);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgfrom_domain      ON '.$name.'(msgfrom_domain);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgto               ON '.$name.'(msgto);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgto_domain        ON '.$name.'(msgto_domain);');
	$dbh->exec('CREATE FULLTEXT INDEX '.$name.'_ind_msgsubject ON '.$name.'(msgsubject);');
}

if (isset($dbCredentials['dsn'])) {
?>
	<p>
<?php
	$notes = array();
	try {
		$dbh = $settings->getDatabase();
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
		$statement = $dbh->prepare('SELECT * FROM spamsettings LIMIT 1;');
		if (!$statement || $statement->execute() === false) {
			$notes[] = 'Adding table spamsettings';
			$dbh->exec('CREATE TABLE spamsettings (access VARCHAR(128), settings TEXT, PRIMARY KEY(access));');
		}
		if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
			foreach ($settings->getMessagelogTables() as $table)
			{
				$statement = $dbh->prepare('SELECT * FROM '.$table.' LIMIT 1;');
				if (!$statement || $statement->execute() === false) {
					createMessageLog($dbh, $notes, $table);
				}
			}
			$statement = $dbh->prepare('SELECT * FROM stat LIMIT 1;');
			if (!$statement || $statement->execute() === false) {
				$notes[] = 'Adding table stat';
				$useridtype = $settings->getPartitionType() == 'string' ? 'VARCHAR(256)' : 'BIGINT';
				$dbh->exec('CREATE TABLE IF NOT EXISTS stat (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, userid '.$useridtype.', domain VARCHAR(300), year INT, month INT, reject INT, deliver INT, INDEX (userid,domain), CONSTRAINT UNIQUE (domain,year,month));');
			}
		} else {
			$notes[] = 'Did not add messagelog because other database than MySQL was used';
		}
		if (!empty($notes))
			echo 'Database<ul><li>'.implode('<li>', $notes).'</ul>';
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
		<strong>You should now remove <code>install.php</code> or create a blank file next to it called <code>installed.txt</code> to proceed.</strong>
	</p>
<?php if (count($settings->getNodes()) > 0) { ?>
	<p>
		<em>INFO:</em> This is a sample authentication script (API script) to be used on your Halon email gateway.<br>
		<pre><?php
			require_once BASE.'/inc/utils/hsl.inc.php';
			echo hsl_script();
		?></pre>
	</p>
<?php } ?>
<?php } else { ?>
	<p>
		<strong>System configuration is incomplete. Edit settings.php</strong>
	</p>
<?php } ?>
	</div>
</body>
</html>
