<?php
if (!defined('SP_ENDUSER')) die('File not included');

if (!file_exists(BASE.'/settings.php'))
	die('Missing '.BASE.'/settings.php; edit <b>settings-default.php</b> and rename it to <b>settings.php</b>.');

require_once BASE.'/inc/core.php';
?>
<!DOCTYPE html>
<html>
<head>
	<title>Install | <?php echo $settings->getPageName(); ?></title>
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
	<p><em>WARNING:</em> cURL extension is missing. Without it, the UI will run slower (not being able to run searches in parallel). <em>(Install the <code>php-curl</code> package.)</em></p>
<?php
}
if (!in_array('openssl', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> OpenSSL extension is missing. Without it, generated passwords will be insecure! <em>(Install the <code>php-openssl</code> package.)</em></p>
<?php
}
if (!in_array('dom', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> DOM extension is missing. Without it, email previews may be incomplete. <em>(Install the <code>php-xml</code> package.)</em></p>
<?php
}
if (!in_array('hash', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> HASH extension is missing. Without it, we are unable to securily store passwords. <em>(Install the <code>php-hash</code> package.)</em></p>
<?php
}
if (!in_array('gettext', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> GETTEXT extension is missing. Without it, you will be unable to get correct character encodings. <em>(Install the <code>php-gettext</code> package.)</em></p>
<?php
}
if (!in_array('session', get_loaded_extensions())) {
# Soap requires session
?>
	<p><em>WARNING:</em> SESSION extension is missing. Without it, you will be unable to keep track of the users supplied credentials. <em>(Install the <code>php-session</code> package.)</em></p>
<?php
}
if (!in_array('soap', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> SOAP extension is missing. Without it, you will be unable to connect directly to nodes. <em>(Install the <code>php-soap</code> package.)</em></p>
<?php
}
if (!in_array('ldap', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> LDAP extension is missing. Without it, you will not be able to use LDAP for authentication. <em>(Install the <code>php-ldap</code> package.)</em></p>
<?php
}
if (!in_array('rrd', get_loaded_extensions())) {
?>
	<p><em>WARNING:</em> RRD extension is missing. Without it, you will be unable to show graphs. <em>(Install the <code>php-rrd</code> package.)</em></p>
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
	global $serialtype;
	$useridtype = $settings->getPartitionType() == 'string' ? 'VARCHAR(256)' : 'BIGINT';
	$uuidcolumn = '';

	if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql')
		$uuidcolumn = 'guid UUID UNIQUE DEFAULT gen_random_uuid(), ';

	$notes[] = 'Adding table '.$name;
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbh->exec('CREATE TABLE '.$name.' (id '.$serialtype.', '.$uuidcolumn.'userid '.$useridtype.' DEFAULT NULL, owner VARCHAR(300), owner_domain VARCHAR(300), msgts0 TIMESTAMP DEFAULT CURRENT_TIMESTAMP, msgts INT, msgid VARCHAR(100), msgactionid INT, msgaction VARCHAR(50), msglistener VARCHAR(100), msgtransport VARCHAR(100), msgsasl VARCHAR(300), msgfromserver VARCHAR(300), msgfrom VARCHAR(300), msgfrom_domain VARCHAR(300), msgto VARCHAR(300), msgto_domain VARCHAR(300), msgsubject TEXT, msgsize INTEGER, score_rpd NUMERIC(10,5), score_sa NUMERIC(10,5), scores TEXT, msgdescription TEXT, serialno VARCHAR(100));');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgid               ON '.$name.'(msgid);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_userid              ON '.$name.'(userid);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_owner               ON '.$name.'(owner);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_owner_domain        ON '.$name.'(owner_domain);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgfromserver       ON '.$name.'(msgfromserver);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgfrom             ON '.$name.'(msgfrom);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgfrom_domain      ON '.$name.'(msgfrom_domain);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgto               ON '.$name.'(msgto);');
	$dbh->exec('CREATE INDEX '.$name.'_ind_msgto_domain        ON '.$name.'(msgto_domain);');
	if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
		$dbh->exec('CREATE FULLTEXT INDEX '.$name.'_ind_msgsubject ON '.$name.'(msgsubject);');
	if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql')
		$dbh->exec('CREATE INDEX '.$name.'_ind_msgsubject ON '.$name." USING gist(to_tsvector('simple', 'msgsubject'));");
}

if (isset($dbCredentials['dsn'])) {
?>
	<p>
<?php
	$notes = array();
	try {
		$dbh = $settings->getDatabase();

		$serialtype = 'BIGSERIAL PRIMARY KEY'; // fallback
		if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
			$serialtype = 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY';
		else if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql')
			$serialtype = 'BIGSERIAL PRIMARY KEY';
		else if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite')
			$serialtype = 'INTEGER PRIMARY KEY AUTOINCREMENT';
		else
			$notes[] = 'Unsupported database';

		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		$statement = $dbh->prepare('SELECT * FROM users LIMIT 1;');
		if (!$statement || $statement->execute() === false) {
			$notes[] = 'Adding table users';
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$dbh->exec('CREATE TABLE users (username VARCHAR(128), password TEXT, reset_password_token TEXT, reset_password_timestamp INTEGER, PRIMARY KEY(username));');
		}
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		$statement = $dbh->prepare('SELECT * FROM users_relations LIMIT 1;');
		if (!$statement || $statement->execute() === false) {
			$notes[] = 'Adding table users_relations';
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$dbh->exec('CREATE TABLE users_relations (username VARCHAR(128), type VARCHAR(32), access VARCHAR(128), PRIMARY KEY(username, type, access), FOREIGN KEY (username) REFERENCES users(username) ON UPDATE CASCADE ON DELETE CASCADE);');
		}
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		$statement = $dbh->prepare('SELECT * FROM users_disabled_features LIMIT 1;');
		if (!$statement || $statement->execute() === false) {
			$notes[] = 'Adding table users_disabled_features';
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$dbh->exec('CREATE TABLE users_disabled_features (username VARCHAR(128), feature VARCHAR(32), PRIMARY KEY(username, feature), FOREIGN KEY (username) REFERENCES users(username) ON UPDATE CASCADE ON DELETE CASCADE);');
		}
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		$statement = $dbh->prepare('SELECT * FROM bwlist LIMIT 1;');
		if (!$statement || $statement->execute() === false) {
			$notes[] = 'Adding table bwlist';
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$dbh->exec('CREATE TABLE bwlist (access VARCHAR(128), type VARCHAR(32), value VARCHAR(128), PRIMARY KEY(access, type, value));');
		}
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		$statement = $dbh->prepare('SELECT * FROM spamsettings LIMIT 1;');
		if (!$statement || $statement->execute() === false) {
			$notes[] = 'Adding table spamsettings';
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$dbh->exec('CREATE TABLE spamsettings (access VARCHAR(128), settings TEXT, PRIMARY KEY(access));');
		}
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		$statement = $dbh->prepare('SELECT * FROM datastore LIMIT 1;');
		if (!$statement || $statement->execute() === false) {
			$notes[] = 'Adding table datastore';
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$dbh->exec('CREATE TABLE datastore (namespace VARCHAR(128), keyname VARCHAR(128), value TEXT, PRIMARY KEY(namespace, keyname));');
		}
		$messagelogs = $settings->getMessagelogTables();
		if (count($messagelogs)) {
			if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql')
				$dbh->query('CREATE EXTENSION IF NOT EXISTS pgcrypto;');
			foreach ($messagelogs as $table)
			{
				$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
				$statement = $dbh->prepare('SELECT * FROM '.$table.' LIMIT 1;');
				if (!$statement || $statement->execute() === false) {
					createMessageLog($dbh, $notes, $table);
				}
			}
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			$statement = $dbh->prepare('SELECT * FROM stat LIMIT 1;');
			if (!$statement || $statement->execute() === false) {
				$notes[] = 'Adding table stat';
				$useridtype = $settings->getPartitionType() == 'string' ? 'VARCHAR(256)' : 'BIGINT';
				$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$dbh->exec('CREATE TABLE stat (id '.$serialtype.', userid '.$useridtype.', direction VARCHAR(300), domain VARCHAR(300), year INT, month INT, reject INT, deliver INT, UNIQUE (direction,domain,year,month));');
				$dbh->exec('CREATE INDEX stat_ind_userid ON stat(userid);');
			}
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
