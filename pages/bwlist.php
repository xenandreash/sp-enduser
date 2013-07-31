<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once('inc/core.php');

if (!isset($settings['database']['dsn']))
	die('No database configured');

function checkAccess($perm)
{
	$access = Session::Get()->getAccess();
	if (count($access) == 0)
		return true;
	foreach($access as $type) {
		foreach($type as $item) {
			if ($item == $perm)
				return true;
		}
	}
	return false;
}

$dbh = new PDO($settings['database']['dsn'], $settings['database']['user'], $settings['database']['password']);

if ($_GET['list'] == 'delete') {
	if (checkAccess($_GET['access'])) {
		$statement = $dbh->prepare("DELETE FROM bwlist WHERE access = :access AND bwlist.type = :type AND bwlist.value = :value;");
		$statement->execute(array(':access' => $_GET['access'], ':type' => $_GET['type'], ':value' => $_GET['value']));
	}
	header("Location: ?page=bwlist");
	die();
}

if ($_GET['list'] == 'add') {
	if (checkAccess($_POST['access']) && ($_POST['type'] == 'whitelist' || $_POST['type'] == 'blacklist')) {
		$statement = $dbh->prepare("INSERT INTO bwlist (access, type, value) VALUES(:access, :type, :value);");
		$statement->execute(array(':access' => $_POST['access'], ':type' => $_POST['type'], ':value' => $_POST['value']));
	}
	header("Location: ?page=bwlist");
	die();
}

$title = 'Black/whitelist';
require_once('inc/header.php');
?>
			</div>
			<div class="halfpages">
				<div class="halfpage">
					<table class="list pad">
						<thead>
							<tr>
								<th>Type</th>
								<th>Sender</th>
								<th>For recipient</th>
								<th style="width: 20px"></th>
							</tr>
						</thead>
					<tbody>
					<?php
					$result = array();

					$access = Session::Get()->getAccess();
					if (count($access) == 0) {
						$statement = $dbh->prepare("SELECT * FROM bwlist ORDER BY type DESC;");
						$statement->execute();
						while ($row = $statement->fetch())
							$result[] = $row;
					}

					foreach($access as $type) {
						foreach($type as $item) {
							$statement = $dbh->prepare("SELECT * FROM bwlist WHERE access = :access ORDER BY type DESC;");
							$statement->execute(array(':access' => $item));
							while ($row = $statement->fetch())
								$result[] = $row;
						}
					}

					foreach($result as $row) {
						?>
						<tr>
							<td><?php p($row['type']); ?> </td>
							<td><?php p($row['value']); ?></td>
							<td><?php p($row['access']); ?></td>
							<td>
								<a title="Remove" class="icon close" href=?page=bwlist&list=delete&access=<?php p($row['access']) ?>&type=<?php p($row['type']) ?>&value=<?php p($row['value']) ?>></a>
							</td>
						</tr>
						<?php
					}
					if (count($result) == 0)
						echo "<tr><td colspan=4 class=semitrans>No black/whitelist</td></tr>";
					?>
					</tbody>
				</table>
			</div>
			<div class="halfpage">
				<fieldset>
					<legend>Black/whitelist</legend>
					<form action="?page=bwlist&list=add" method="post">
						<div>
							<label>Action</label>
							<select name="type">
								<option value="blacklist">Blacklist</option>
								<option value="whitelist">Whitelist</option>
							</select>
						</div>
						<div>
							<label>Sender</label>
							<input type="text" name="value">
						</div>
						<div>
							<label>For recipient</label>
							<?php
								$_access = array();
								foreach($access as $a) {
									foreach($a as $type) {
										$_access[] = $type;
									}
								}
								if (count($_access) > 0) {
							?>
							<select name="access">
							<?php foreach($_access as $a) { ?>
								<option><?php echo $a; ?></option>
							<?php } ?>
							</select>
							<?php } else { ?>
							<input type="text" name="access" placeholder="everyone">
							<?php } ?>
						</div>
						<div>
							<label></label>
							<button type="submit">Add</button>
						</div>
						<br>
						<div>
							Sender may be an IP address, an e-mail address, a domain name or a wildcard domain name starting with a dot (eg. .co.uk).
						</div>
					</form>
				</fieldset>
			</div>
		</div>
<?php require_once('inc/footer.php'); ?>
