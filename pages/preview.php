<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

$id = intval($_GET['id']);

if ($_GET['type'] == 'log') {
	// Fetch data from local SQL log
	$node = 'local';
	$mail = restrict_local_mail($id);
} else {
	// Fetch data from SOAP
	$node = intval($_GET['node']);
	$mail = restrict_mail($_GET['type'], $node, $id);
	$client = soap_client($node);
}

if (isset($_POST['action'])) {
	if ($_POST['action'] == 'bounce')
		$client->mailQueueBounce(array('id' => $id));
	if ($_POST['action'] == 'delete')
		$client->mailQueueDelete(array('id' => $id));
	if ($_POST['action'] == 'retry')
		$client->mailQueueRetry(array('id' => $id));
	$title = 'Message';
	require_once BASE.'/partials/header.php'; ?>
				<div class="item">
					<div class="button back" onclick="location.href='<?php p($_POST['referer']) ?>';">Back</div>
				</div>
			</div>
			<div class="pad message ok">The requested action has been performed</div>
	<?php require_once BASE.'/partials/footer.php';
	die();
}

// Prepare data
$scores = $settings->getDisplayScores();
$logs = $settings->getDisplayTextlog();
$transports = $settings->getDisplayTransport();
$listeners = $settings->getDisplayListener();
if (isset($transports[$mail->msgtransport])) $transport = $transports[$mail->msgtransport];
if (isset($listeners[$mail->msglistener])) $listener = $listeners[$mail->msglistener];
if ($_GET['type'] == 'queue' && $mail->msgaction == 'DELIVER')
	$desc = 'In queue (retry '.$mail->msgretries.') <span class="semitrans">'.htmlspecialchars($mail->msgerror).'</span>';
else
	$desc = htmlspecialchars($mail->msgdescription);
if ($_GET['type'] == 'queue') {
	$uniq = uniqid();
	$command = array('previewmessage', $mail->msgpath, $uniq);
	if ($mail->msgdeltapath)
		$command[] = $mail->msgdeltapath;
	$data = soap_exec($command, $client);
	$data = str_replace("\r\n", "\n", $data);
	$data = explode("$uniq|", $data);
	$result = array();
	$result['HEADERS'] = trim($data[0]);
	for ($i = 1; $i < count($data); ++$i) {
		list($type, $content) = explode("\n", $data[$i], 2);
		$result[$type] = trim($content);
	}
	if (isset($result['TEXT']) || isset($result['HTML'])) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$config->set('URI.Disable', true);
		$purifier = new HTMLPurifier($config);
		$header = $result['HEADERS'];
		$headerdelta = $result['HEADERS-DELTA'];
		$attachments = $result['ATTACHMENTS'] != "" ? array_map(function ($k) { return explode('|', $k); }, explode("\n", $result['ATTACHMENTS'])) : array();

		$body = isset($result['TEXT']) ? htmlspecialchars($result['TEXT']) : $result['HTML'];
		$body = trim($purifier->purify($body));
		$encode = isset($result['TEXT']) ? 'TEXT' : 'HTML';
	} else {
		$encode = 'TEXT';
		$body = 'Preview not available';
	}
}

$title = 'Viewing Message';
$javascript[] = 'static/js/preview.js';
$javascript[] = 'static/js/diff_match_patch.js';
$javascript[] = 'static/js/diff.js';
require_once BASE.'/partials/header.php';
?>
	<div class="container-fluid">
			<!--<form>
				<div class="item">
					<div class="button back" onclick="history.back()">Back</div>
				</div>
				<?php if ($logs) { ?>
				<div class="item">
					<a href="?page=log&id=<?php p($id) ?>&node=<?php p($node) ?>&type=<?php p($_GET['type']) ?>"><div class="button search">Text log</div></a>
				</div>
				<?php } ?>
				<?php if ($_GET['type'] == 'queue') { ?>
				<div class="item">
					<a href="?page=download&id=<?php p($id) ?>&node=<?php p($node) ?>"><div class="button down">Download</div></a>
				</div>
				<div class="item">
					<div class="button start tracking-actions">Actions...</div>
				</div>
				<?php } ?>
			</form>
		</div>-->
		<!-- <div class="row"> -->
			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Metadata</h3>
					</div>
					<div class="panel-body">
						<dl class="dl-horizontal">
							<?php if ($listener) { ?>
								<dt>Received by</dt>
								<dd><?php echo $listener ?></dd>
							<?php } ?>
							
							<dt>Date</dt>
							<dd><?php p(strftime('%Y-%m-%d %H:%M:%S', $mail->msgts0 - $_SESSION['timezone'] * 60)) ?></dd>
							
							<dt>Server</dt><dd><?php p($mail->msgfromserver) ?></dd>
							<?php if ($mail->msgsasl) { ?><dt>User</dt><dd><?php p($mail->msgsasl) ?></dd><?php } ?>
							
							<dt>From</dt>
							<dd><?php p($mail->msgfrom) ?></dd>
							
							<dt>To</dt>
							<dd><?php p($mail->msgto) ?></dd>
							
							<dt>Subject</dt>
							<dd><?php p($mail->msgsubject) ?></dd>
							
							<dt>Action</dt>
							<dd><?php p(ucfirst(strtolower($mail->msgaction))) ?></dd>
							
							<?php if ($desc) { ?>
							<dt>Details</dt>
							<dd><?php echo $desc ?></dd>
							<?php } ?>
							
							<?php if ($transport) { ?>
							<dt>Destination</dt>
							<dd><?php echo $transport ?></dd>
							<?php } ?>
							
							<dt>ID</dt>
							<dd><?php p($mail->msgid) ?></dd>
						</dl>
					</div>
				</div>
				
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Scores</h3>
					</div>
					<?php if ($scores) { ?>
						<table class="table">
							<thead>
								<tr>
									<th>Engine</th>
									<th>Result</th>
									<th class="hidden-xs">Signature</th>
								</tr>
							</thead>
							<tbody>
							<?php
							$scores = history_parse_scores($mail);
							foreach ($scores as $score) { ?>
								<tr>
								<td><?php p($score['name']) ?></td>
								<td><?php p($score['score']) ?></td>
								<td class="semitrans hidden-xs"><?php p($score['text']) ?></td>
								</tr>
							<?php } ?>
							</tbody>
						</table>
					<?php } ?>
				</div>
			</div>
			
			<div class="col-md-8">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Content</h3>
					</div>
					<div class="panel-body">
						<div class="msg-body">
							<?php
							if ($encode == 'TEXT')
								echo '<pre>'.$body.'</pre>';
							else if ($body)
								echo $body;
							?>
						</div>
					</div>
					
					<?php if (count($attachments) > 0) { ?>
					<div class="panel-footer">
						<ul class="list-inline">
							<?php foreach ($attachments as $i => $a) { ?>
								<li>
									<i class="glyphicon glyphicon-paperclip"></i>
									<?php p($a[2]); ?>&nbsp;<small class="text-muted">(<?php p(round($a[1]/1024, 0)); ?>KiB)</small>
								</li>
							<?php } ?>
						</ul>
					</div>
					<?php } ?>
				</div>
				
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Headers</h3>
					</div>
					<div class="panel-body">
						<?php if ($header != '') { ?>
							<div class="msg-header" style="clear:both;margin-top:5px;">
								<div style="float:left;margin:5px;height:8px;width:8px;background-color:#ddffdd;border: 1px solid #ccc;"></div>
								<div style="float:left;font-size:10px;padding-top:5px;color:green;margin-right:10px;">Added</div>
								<div style="float:left;margin:5px;height:8px;width:8px;background-color:#ffdddd;border: 1px solid #ccc;"></div>
								<div style="float:left;font-size:10px;padding-top:5px;color:red;">Removed</div>
								<div class="preview-headers"></div>
							</div>
							<script>
								var headers_original = <?php echo json_encode($header); ?>;
								var headers_modified = <?php echo json_encode($headerdelta); ?>;
								$(".preview-headers").html(diff_lineMode(headers_original,
									headers_modified ? headers_modified : headers_original, true));
							</script>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		
		<form id="actionform" method="post" action="?page=preview&node=<?php p($node) ?>&id=<?php p($id) ?>">
			<input type="hidden" name="action" id="action" value="">
			<input type="hidden" name="referer" id="referer" value="<?php p(isset($_POST['referer']) ? $_POST['referer'] : $_SERVER['HTTP_REFERER']); ?>">
		</form>
	</div>
<?php require_once BASE.'/partials/footer.php'; ?>
