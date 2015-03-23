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
	foreach ($_POST['access'] as $access)
	{
		if (strpos($_POST['value'], ' ') !== false) die('Invalid email address, domain name or IP address.');
		if (strpos($access, ' ') !== false) die('Invalid email address or domain name.');
		if ($_POST['value'][0] == '@') $_POST['value'] = substr($_POST['value'], 1);
		if ($access[0] == '@') $access = substr($access, 1);
		if (checkAccess($access) && ($_POST['type'] == 'whitelist' || $_POST['type'] == 'blacklist')) {
			$statement = $dbh->prepare("INSERT INTO bwlist (access, type, value) VALUES(:access, :type, :value);");
			$statement->execute(array(':access' => strtolower($access), ':type' => $_POST['type'], ':value' => strtolower($_POST['value'])));
		}
	}
	header("Location: ?page=bwlist");
	die();
}

$title = 'Black/whitelist';
require_once BASE.'/partials/header.php';

$row_classes = array(
	'whitelist' => 'success',
	'blacklist' => 'danger',
);

function print_row($type, $value, $accesses, $icon = '') {
	$access = implode(', ', $accesses);
?>
							<td class="hidden-xs"><?php p($type); ?></td>
							<td class="hidden-xs"><?php p($value); ?></td>
							<td class="hidden-xs"><?php p($access); ?></td>
							<td class="visible-xs">
								<p>
									<i class="glyphicon glyphicon-pencil"></i>&nbsp;
									<?php p($value); ?>
								</p>
								<p>
									<i class="glyphicon glyphicon-inbox"></i>&nbsp;
									<?php p($access); ?>
								</p>
							</td>
							<td style="width: 30px; vertical-align: middle">
								<?php echo $icon ?>
							<?php if (count($accesses) == 1) { ?>
								<a title="Remove" href="?page=bwlist&list=delete&access=<?php p($accesses[0]) ?>&type=<?php p($type) ?>&value=<?php p($value) ?>"><i class="glyphicon glyphicon-remove"></i></a>
							<?php } ?>
							</td>
<?php
}

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

// For users with many access levels; print them more condensed
$result2 = array();
foreach ($result as $row)
	$result2[$row['type']][$row['value']][] = $row['access'];

?>
	<div class="container-fluid">
		<div class="col-md-6 col-lg-8">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Black/whitelist</h3>
				</div>
				<table class="table">
					<thead class="hidden-xs">
						<tr>
							<th class="hidden-xs" style="width: 100px">Type</th>
							<th class="hidden-xs">Sender</th>
							<th class="hidden-xs">For recipient</th>
							<th class="visible-xs"></th>
							<th style="width: 30px"></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$id = 0;
						foreach ($result2 as $type => $items) {
							foreach ($items as $value => $accesses) {
								if (count($accesses) > 1) {
									$id++;
						?>
						<tr style="cursor:pointer" data-toggle="<?php p($id) ?>" class="toggle <?php p($row_classes[$type] ?: 'info'); ?>">
						<?php print_row($type, $value, $accesses, '<span class="expand-icon glyphicon glyphicon-expand"></span>') ?>
						</tr>
						<?php
									foreach ($accesses as $access) {
						?>
						<tr style="display:none" class="hidden-<?php p($id) ?> <?php p($row_classes[$type] ?: 'info'); ?>">
						<?php print_row($type, $value, array($access)) ?>
						</tr>
						<?php
									}
								} else {
						?>
						<tr class="<?php p($row_classes[$type] ?: 'info'); ?>">
						<?php print_row($type, $value, $accesses) ?>
						</tr>
						<?php
								}
							}
						}
						if (count($result) == 0)
							echo '<tr><td colspan="4" class="text-muted">No black/whitelist</td></tr>';
						?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="col-md-6 col-lg-4">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Add...</h3>
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
							<label class="control-label col-md-3" style="white-space: nowrap;">For recipient</label>
							<div class="col-md-9">
								<?php
									$_access = array();
									foreach (Session::Get()->getAccess() as $a) {
										foreach ($a as $type) {
											$_access[] = $type;
										}
									}
									if (count($_access) == 1) {
								?>
									<input type="hidden" class="form-control" name="access[]" value="<?php p($_access[0]); ?>">
									<p class="form-control-static"><?php p($_access[0]); ?></p>
								<?php
									} else if (count($_access) > 0) {
								?>
									<button id="check-all" class="btn btn-info">Select all</button>
									<?php foreach ($_access as $a) { ?>
									<div class="checkbox">
										<label>
											<input type="checkbox" class="recipient" name="access[]" value="<?php p($a); ?>">
											<?php p($a); ?>
										</label>
									</div>
									<?php } ?>
								</select>
								<?php } else { ?>
									<input type="text" class="form-control" name="access[]" placeholder="everyone">
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
	<style>
		table {
			table-layout: fixed;
		}
		td {
			text-overflow: ellipsis;
			white-space: nowrap;
			overflow: hidden;
		}
	</style>
	<script>
	$(document).ready(function() {
		$('#check-all').click(function() {
				$('input.recipient').prop('checked', true);
				return false;
		});
		$(".toggle").click(function() {
			$(".hidden-" + $(this).data("toggle")).toggle();
			var icon = $(this).find(".expand-icon");
			if (icon.hasClass('glyphicon-expand'))
				icon.addClass('glyphicon-collapse-down').removeClass('glyphicon-expand');
			else
				icon.addClass('glyphicon-expand').removeClass('glyphicon-collapse-down');
		});
	});
	</script>
<?php require_once BASE.'/partials/footer.php'; ?>
