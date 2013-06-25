<?php
if(!defined('SP_ENDUSER')) die('File not included');

require_once('inc/core.php');
require_once('inc/utils.php');

if (isset($_POST['delete']) || isset($_POST['bounce']) || isset($_POST['retry'])) {
	$actions = array();
	foreach ($_POST as $k => $v) {
		if (!preg_match('/^multiselect-(\d+)$/', $k, $m))
			continue;

		$node = $v;
		$queueid = intval($m[1]);
		$client = soap_client($node);

		// Access permission
		$query['filter'] = build_query_restrict().' && queueid='.$queueid;
		$query['offset'] = 0;
		$query['limit'] = 1;
		$queue = $client->mailQueue($query);
		if (count($queue->result->item) != 1)
			die('Invalid queueid');

		$actions[$v][] = $m[1];
	}
	foreach($actions as $soapid => $list)
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
require_once('inc/header.php');

// Default values
$search = isset($_GET['search']) ? hql_transform($_GET['search']) : '';
$size = isset($_GET['size']) ? $_GET['size'] : 50;
$source = isset($_GET['source']) ? $_GET['source'] : 'quarantine';

// Select box arrays
foreach (array(10, 50, 100, 500, 1000, 5000) as $n)
	$pagesize[$n] = $n.' results';
$sources = array('all' => 'All', 'history' => 'History', 'queue' => 'Queue', 'quarantine' => 'Quarantine');

// Create actual search query, in order, of importance
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
$total = 0;
$prev_button = ' disabled';
$next_button = ' disabled';
$param = array();
$clients = array();
foreach ($settings['node'] as $n => $r) {
	$param['queue'][$n]['limit'] = $size + 1;
	$param['history'][$n]['limit'] = $size + 1;
	$param['queue'][$n]['filter'] = $real_search;
	$param['history'][$n]['filter'] = $real_search;
	$param['queue'][$n]['offset'] = 0;
	$param['history'][$n]['offset'] = 0;
	$clients[$n] = soap_client($n);
}

// Override with GET
foreach ($_GET as $k => $v) {
	if (!preg_match('/^(history|queue)offset(\d+)$/', $k, $m))
		continue;
	if ($v < 1)
		continue;
	$param[$m[1]][$m[2]]['offset'] = $v;
	$prev_button = '';
}

// Perform actual requests
if ($source == 'all' || $source == 'history') {
	foreach ($settings['node'] as $n => $r) {
		$data = $clients[$n]->mailHistory($param['history'][$n]);
		if (is_array($data->result->item)) foreach($data->result->item as $item)
			$timesort[$item->msgts][] = array('id' => $n, 'type' => 'history', 'data' => $item);
		$total += $data->totalHits;
	}
}
if ($source == 'all' || $source == 'queue' || $source == 'quarantine') {
	foreach ($settings['node'] as $n => $r) {
		$data = $clients[$n]->mailQueue($param['queue'][$n]);
		if (is_array($data->result->item)) foreach($data->result->item as $item)
			$timesort[$item->msgts][] = array('id' => $n, 'type' => 'queue', 'data' => $item);
		$total += $data->totalHits;
	}
}
krsort($timesort);
?>
			<form>
				<div class="item">
					<input type="search" size="40" placeholder="any" name="search" value="<?php echo htmlspecialchars($search) ?>">
					<label>Search query</label>
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
		<table class="list pad fixed">
			<thead>
				<tr>
					<th style="width: 125px">Date and time</th>
					<th style="width: 20px" class="action"><input type="checkbox" id="select-all"></th>
					<th>From</th>
					<th>To</th>
					<th>Subject</th>
					<th></th>
					<th style="width: 40px"></th>
				</tr>
			</thead>
			<tbody>
			<form method="post" id="multiform">
			<?php
			$i = 1;
			foreach($timesort as $t) {
				if ($i > $size) {
					$next_button = ''; // enable "next" page button
					break;
				}
				foreach($t as $m) {
					if ($i > $size) {
						$next_button = ''; // enable "next" page button
						break;
					}
					$i++;
					$param[$m['type']][$m['id']]['offset']++;
				?>
				<tr>
					<td><span class="semitrans">
						<?php echo strftime('%Y-%m-%d %H:%M:%S', $m['data']->msgts) ?>
					</span></td>
					<td class="action <?php echo $m['data']->msgaction.' '.$m['type'] ?>" title="<?php p($m['data']->msgaction) ?>">
					<?php if ($m['type'] == 'queue') { // queue or quarantine ?>
						<input type="checkbox" name="multiselect-<?php echo $m['data']->id ?>" value="<?php echo $m['id'] ?>">
					<?php } else { // history ?>
						<strong><?php echo $m['data']->msgaction[0] ?></strong>
					<?php } ?>
					</td>
					<td><?php p($m['data']->msgfrom) ?></td>
					<td><?php p($m['data']->msgto) ?></td>
					<td>
						<a href="?page=preview&node=<?php echo $m['id'] ?>&queueid=<?php echo $m['data']->id ?>"><?php p($m['data']->msgsubject) ?></a>
					</td>
					<td>
					<?php if ($m['type'] == 'queue' && $m['datd']->msgaction == 'DELIVER') { // queue ?>
						In queue (retry <?php echo $m['data']->msgretries ?>)
						<span class="semitrans"><?php p($m['data']->msgerror) ?></span>
					<?php } else { // history or quarantine ?>
						<span class="semitrans"><?php p($m['data']->msgdescription) ?></span>
					<?php } ?>
					</td>
					<td>
						<a title="Preview" class="icon mail" href="?page=preview&node=<?php echo $m['id'] ?>&queueid=<?php echo $m['data']->id ?>"></a>
						<div title="Release/retry" class="icon go"></div>
					</td>
				</tr>
			<?php }} ?>
			<?php if (empty($timesort)) { ?>
				<tr>
					<td colspan="7"><span class="semitrans">No matches</span></td>
				</tr>
			<?php } ?>
			</form>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="7" style="text-align: center">
						<form>
							<button type="button" name="prev" <?php echo $prev_button ?> style="float: left" onclick="history.go(-1)">Previous</button>
							<button type="submit" name="next" <?php echo $next_button ?> style="float: right">Next</button>
							<input type="hidden" name="size" value="<?php p($size) ?>">
							<input type="hidden" name="search" value="<?php p($search) ?>">
							<input type="hidden" name="source" value="<?php p($source) ?>">
							<?php foreach ($param as $type => $nodes) { foreach ($nodes as $node => $args) { ?>
								<input type="hidden" name="<?php echo $type ?>offset<?php echo $node ?>" value="<?php p($args['offset']) ?>">
							<?php }} ?>
							<span class="semitrans">
								<?php p(number_format($total)); ?> match<?php $total != 1 ? p('es') : ''; ?> found
							</span>
						</form>
					</td>
				</tr>
			</tfoot>
		</table>
<?php require_once('inc/footer.php'); ?>
