<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';

function checkAccess($perm)
{
	$access = Session::Get()->getAccess();
	if (count($access) == 0)
		return true;
	foreach ($access as $type) {
		foreach ($type as $item) {
			if ($item == $perm)
				return true;
		}
	}
	return false;
}

$dbh = $settings->getDatabase();

if ($_GET['list'] == 'delete') {
	if (checkAccess($_GET['access'])) {
		$statement = $dbh->prepare("DELETE FROM bwlist WHERE access = :access AND bwlist.type = :type AND bwlist.value = :value;");
		$statement->execute(array(':access' => $_GET['access'], ':type' => $_GET['type'], ':value' => $_GET['value']));
	}
	header("Location: ?page=bwlist");
	die();
}

if ($_GET['list'] == 'add') {
	if (strpos($_POST['value'], ' ') !== false) die('Invalid email address, domain name or IP address.');
	if (strpos($_POST['access'], ' ') !== false) die('Invalid email address or domain name.');
	if ($_POST['value'][0] == '@') $_POST['value'] = substr($_POST['value'], 1);
	if ($_POST['access'][0] == '@') $_POST['access'] = substr($_POST['access'], 1);
	if (checkAccess($_POST['access']) && ($_POST['type'] == 'whitelist' || $_POST['type'] == 'blacklist')) {
		$statement = $dbh->prepare("INSERT INTO bwlist (access, type, value) VALUES(:access, :type, :value);");
		$statement->execute(array(':access' => strtolower($_POST['access']), ':type' => $_POST['type'], ':value' => strtolower($_POST['value'])));
	}
	header("Location: ?page=bwlist");
	die();
}

$title = 'Black/whitelist';
require_once BASE.'/partials/header.php';
?>
	<div class="container-fluid">
		<div class="col-md-6 col-lg-8">
			<table class="table">
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

					foreach ($access as $type) {
						foreach ($type as $item) {
							$statement = $dbh->prepare("SELECT * FROM bwlist WHERE access = :access ORDER BY type DESC;");
							$statement->execute(array(':access' => $item));
							while ($row = $statement->fetch())
								$result[] = $row;
						}
					}

					foreach ($result as $row) {
					?>
					<tr>
						<td><?php p($row['type']); ?> </td>
						<td><?php p($row['value']); ?></td>
						<td><?php p($row['access']); ?></td>
						<td>
							<a title="Remove" href="?page=bwlist&list=delete&access=<?php p($row['access']) ?>&type=<?php p($row['type']) ?>&value=<?php p($row['value']) ?>"><i class="glyphicon glyphicon-remove"></i></a>
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
		<div class="col-md-6 col-lg-4">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Black/whitelist</h3>
				</div>
				<div class="panel-body">
					<form class="form-horizontal" action="?page=bwlist&list=add" method="post">
						<div class="form-group">
							<label for="type" class="control-label col-md-3">Action</label>
							<div class="col-md-9">
								<select name="type" class="form-control">
									<option value="blacklist">Blacklist</option>
									<option value="whitelist">Whitelist</option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3">Sender</label>
							<div class="col-md-9">
								<input type="text" class="form-control" name="value">
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3">For recipient</label>
							<div class="col-md-9">
								<?php
									$_access = array();
									foreach ($access as $a) {
										foreach ($a as $type) {
											$_access[] = $type;
										}
									}
									if (count($_access) > 0) {
								?>
								<select name="access" class="form-control">
									<?php foreach ($_access as $a) { ?>
										<option><?php echo $a; ?></option>
									<?php } ?>
								</select>
								<?php } else { ?>
									<input type="text" class="form-control" name="access" placeholder="everyone">
								<?php } ?>
								<p class="help-block">
									Sender may be an IP address, an e-mail address, a domain name or a wildcard domain name starting with a dot (eg. .co.uk).
								</p>
							</div>
						</div>
						<div class="col-md-offset-3 col-md-9">
							<button type="submit" class="btn btn-primary">Add</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
<?php require_once BASE.'/partials/footer.php'; ?>
