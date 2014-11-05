<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

if (isset($_POST['delete']) || isset($_POST['bounce']) || isset($_POST['retry'])) {
	$actions = array();
	foreach ($_POST as $k => $v) {
		if (!preg_match('/^multiselect-(\d+)$/', $k, $m))
			continue;

		$node = $v;
		$id = intval($m[1]);
		$client = soap_client($node);

		// Access permission
		restrict_mail('queue', $node, $id); // Dies if access is denied
		$actions[$v][] = $id;
	}
	foreach ($actions as $soapid => $list)
	{
		$id = implode(',', $list);
		if (isset($_POST['bounce']))
			soap_client($soapid)->mailQueueBounce(array('id' => $id));
		if (isset($_POST['delete']))
			soap_client($soapid)->mailQueueDelete(array('id' => $id));
		if (isset($_POST['retry']))
			soap_client($soapid)->mailQueueRetry(array('id' => $id));
	}
	header('Location: '.$_SERVER['REQUEST_URI']);
	die();
}

$title = 'Messages';
$javascript[] = 'static/index.js';
require_once BASE.'/partials/header.php';

// Default values
$search = isset($_GET['search']) ? hql_transform($_GET['search']) : '';
$size = isset($_GET['size']) ? $_GET['size'] : 50;
$size = $size > 5000 ? 5000 : $size;
$source = isset($_GET['source']) ? $_GET['source'] : $settings->getDefaultSource();
$display_scores = $settings->getDisplayScores();

// Select box arrays
foreach (array(10, 50, 100, 500, 1000, 5000) as $n)
	$pagesize[$n] = $n.' results';
$sources = array('history' => 'History', 'queue' => 'Queue', 'quarantine' => 'Quarantine', 'log' => 'Log');

// Create actual search query for SOAP, in order of importance (for security)
$queries = array();
$restrict = build_query_restrict();
if ($restrict != '')
	$queries[] = $restrict;
if ($source == 'queue')
	$queries[] = 'action=DELIVER';
if ($source == 'quarantine')
	$queries[] = 'action=QUARANTINE';
if ($search != '')
	$queries[] = $search;
$real_search = implode(' && ', $queries);

// Initial settings
$timesort = array();
$tasks = array();
$prev_button = ' disabled';
$next_button = ' disabled';
$param = array();
$errors = array();

$dbBackend = new DatabaseBackend($settings->getDatabase());
$nodeBackend = new NodeBackend($settings->getNodes());

// Override offset with GET
$totaloffset = 0;
foreach ($_GET as $k => $v) {
	if (!preg_match('/^(history|queue|log)offset(\d+)$/', $k, $m))
		continue;
	if ($v < 1)
		continue;
	$param[$m[1]][$m[2]]['offset'] = $v;
	$totaloffset += $v;
	$prev_button = '';
}

$cols = 8;

if ($source == 'log') {
	$results = $dbBackend->loadMailHistory($real_search, $size, $errors);
	$timesort = array_merge($timesort, $results);
}
if ($source == 'history') {
	$results = $nodeBackend->loadMailHistory($real_search, $size, $errors);
	$timesort = array_merge($timesort, $results);
}
if ($source == 'queue' || $source == 'quarantine') {
	$results = $nodeBackend->loadMailQueue($real_search, $size, $errors);
	$timesort = array_merge($timesort, $results);
}

krsort($timesort);
ksort($errors);
?>
			<form>
				<div class="item">
					<input type="search" size="40" placeholder="any" name="search" value="<?php p($search) ?>">
					<label>Search</label>
				</div>
				<div class="item">
					<?php p_select('size', $size, $pagesize) ?>
					<label for="size">Page size</label>
				</div>
				<div class="item">
					<?php p_select('source', $source, $sources) ?>
					<label for="size">Source</label>
				</div>
				<div class="item">
					<button class="search">Search</button>
				</div>
				<div class="item">
					<div class="divider"></div>
				</div>
				<div class="item">
					<div class="button start tracking-actions">Actions...</div>
				</div>
			</form>
		</div>
		<?php if (count($errors)) { ?>
		<p style="padding-left: 17px; padding-top: 17px;">
			<span class="semitrans">
				Some messages might not be available at the moment due to maintenance.
			</span>
		</p>
		<?php } ?>
		<table class="list pad fixed">
			<thead>
				<tr>
					<th style="width: 17px; padding: 0"></th>
					<th style="width: 20px" class="action"><input type="checkbox" id="select-all"></th>
					<th style="width: 125px">Date and time</th>
					<th>From</th>
					<th>To</th>
					<th>Subject</th>
					<?php if ($display_scores) { $cols++ ?><th>Scores</th><?php } ?>
					<th>Details</th>
					<th style="width: 40px"></th>
				</tr>
			</thead>
			<tbody>
			<form method="post" id="multiform">
			<?php
			$i = 1;
			foreach ($timesort as $t) {
				if ($i > $size) {
					$next_button = ''; // enable "next" page button
					break;
				}
				foreach ($t as $m) {
					if ($i > $size) {
						$next_button = ''; // enable "next" page button
						break;
					}
					$i++;
					$param[$m['type']][$m['id']]['offset']++;
					$preview = http_build_query(array(
						'page' => 'preview',
						'node' => $m['id'],
						'id' => $m['data']->id,
						'type' => $m['type']));
				?>
				<tr>
					<td style="width: 17px; padding: 0"></td>
					<td class="action <?php p($m['data']->msgaction.' '.$m['type']) ?>" title="<?php p($m['data']->msgaction) ?>">
					<?php if ($m['type'] == 'queue') { // queue or quarantine ?>
						<input type="checkbox" name="multiselect-<?php p($m['data']->id) ?>" value="<?php p($m['id']) ?>">
					<?php } else { // history ?>
						<strong><?php p($m['data']->msgaction[0]) ?></strong>
					<?php } ?>
					</td>
					<td><span class="semitrans">
						<?php p(strftime('%Y-%m-%d %H:%M:%S', $m['data']->msgts0 - $_SESSION['timezone'] * 60)) ?>
					</span></td>
					<td><?php p($m['data']->msgfrom) ?></td>
					<td><?php p($m['data']->msgto) ?></td>
					<td>
						<a href="?<?php echo $preview ?>"><?php p($m['data']->msgsubject) ?></a>
					</td>
					<?php if ($display_scores) {
						$printscores = array();
						$scores = history_parse_scores($m['data']);
						foreach ($scores as $engine => $s) {
							if ($engine == 'rpd' && $s['score'] != 'Unknown')
								$printscores[] = strtolower($s['score']);
							if ($engine == 'kav' && $s['score'] != 'Ok')
								$printscores[] = 'virus';
							if ($engine == 'clam' && $s['score'] != 'Ok')
								$printscores[] = 'virus';
							if ($engine == 'rpdav' && $s['score'] != 'Ok')
								$printscores[] = 'virus';
							if ($engine == 'sa')
								$printscores[] = $s['score'];
						}
					?>
					<td><?php p(implode(', ', array_unique($printscores))) ?></td>
					<?php } ?>
					<td>
					<?php if ($m['type'] == 'queue' && $m['data']->msgaction == 'DELIVER') { // queue ?>
						In queue (retry <?php p($m['data']->msgretries) ?>)
						<span class="semitrans"><?php p($m['data']->msgerror) ?></span>
					<?php } else { // history or quarantine ?>
						<span class="semitrans"><?php p($m['data']->msgdescription) ?></span>
					<?php } ?>
					</td>
					<td>
						<a title="Details" class="icon mail" href="?<?php echo $preview?>"></a>
					<?php if ($m['type'] != 'history' && $m['type'] != 'log') { ?>
						<div title="Release/retry" class="icon go"></div>
					<?php } ?>
					</td>
				</tr>
			<?php }} ?>
			<?php if (empty($timesort)) { ?>
				<tr>
					<td colspan="<?php p($cols) ?>"><span class="semitrans">No matches</span></td>
				</tr>
			<?php } ?>
			</form>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="<?php p($cols) ?>" style="text-align: center">
						<form>
							<button type="button" name="prev" <?php echo $prev_button ?> style="float: left" onclick="history.go(-1)">Previous</button>
							<button type="submit" name="next" <?php echo $next_button ?> style="float: right">Next</button>
							<input type="hidden" name="size" value="<?php p($size) ?>">
							<input type="hidden" name="search" value="<?php p($search) ?>">
							<input type="hidden" name="source" value="<?php p($source) ?>">
							<?php foreach ($param as $type => $nodes) foreach ($nodes as $node => $args) if ($args['offset'] > 0) { ?>
								<input type="hidden" name="<?php p($type) ?>offset<?php p($node) ?>" value="<?php p($args['offset']) ?>">
							<?php } ?>
						</form>
					</td>
				</tr>
			</tfoot>
		</table>
		<?php if (count($errors)) { ?>
		<div style="padding-left: 17px;">
			<span class="semitrans">
				Diagnostic information:
				<ul>
				<?php foreach ($errors as $n => $error) { ?>
					<li><?php p($n.': '.$error); ?>
				<?php } ?>
				</ul>
			</span>
		</div>
		<?php } ?>
<?php require_once BASE.'/partials/footer.php'; ?>
