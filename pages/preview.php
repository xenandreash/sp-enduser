<?php
if(!defined('SP_ENDUSER')) die('File not included');

require_once('inc/core.php');
require_once('inc/utils.php');

$node = intval($_GET['node']);
$queueid = intval($_GET['queueid']);
$client = soap_client($node);

// Access permission
$query['filter'] = build_query_restrict().' && queueid='.$queueid;
$query['offset'] = 0;
$query['limit'] = 1;
$queue = $client->mailQueue($query);
if (count($queue->result->item) != 1)
	die('Invalid queueid');

if (isset($_POST['action'])) {
	if ($_POST['action'] == 'bounce')
		$client->mailQueueBounce(array('id' => $queueid));
	if ($_POST['action'] == 'delete')
		$client->mailQueueDelete(array('id' => $queueid));
	if ($_POST['action'] == 'retry')
		$client->mailQueueRetry(array('id' => $queueid));
	$title = 'Message';
	require_once('inc/header.php'); ?>
				<div class="item">
					<div class="button back" onclick="history.back()">Back</div>
				</div>
			</div>
			<div class="pad message ok">The requested action has been performed</div>
	<?php require_once('inc/footer.php');
	die();
}

// Prepare data
$mail = $queue->result->item[0];
$uniq = uniqid();
$data = soap_exec(array('previewmessage', $mail->msgpath, $uniq), $client);
$data = str_replace("\r\n", "\n", $data);
if (preg_match("/^(.*)\n$uniq\|ATTACHMENTS\n(.*?)(?:\n)?$uniq\|(HTML|TEXT)\n(.*)$/sm", $data, $result)) {
	require_once('inc/htmlpurifier-4.5.0-lite/library/HTMLPurifier.auto.php');
	$config = HTMLPurifier_Config::createDefault();
	$config->set('Cache.DefinitionImpl', null);
	$config->set('URI.Disable', true);
	$purifier = new HTMLPurifier($config);
	$header = $purifier->purify(htmlspecialchars($result[1]));
	$encode = $result[3];
	$rawbody = $encode == 'TEXT' ? htmlspecialchars($result[4]) : $result[4];
	$body = trim($purifier->purify($rawbody));
	$attachments = array();
	if ($result[2] != '') foreach (explode("\n", $result[2]) as $a)
		$attachments[] = explode('|', $a);
}

$title = 'Message';
$javascript[] = 'static/preview.js';
require_once('inc/header.php');
?>
			<form>
				<div class="item">
					<div class="button back" onclick="history.back()">Back</div>
				</div>
				<div class="item">
					<a href="?page=download&queueid=<?php echo $queueid?>&node=<?php echo $node?>"><div class="button down">Download</div></a>
				</div>
				<div class="item">
					<div class="button start tracking-actions">Actions...</div>
				</div>
			</form>
		</div>
		<div class="fullpage">
			<div class="preview-header">Date</div> <?php p(strftime('%Y-%m-%d %H:%M:%S', $mail->msgts)) ?><br>
			<div class="preview-header">Server</div> <?php p($mail->msgfromserver) ?><br>
			<div class="preview-header">From</div> <?php p($mail->msgfrom) ?><br>
			<div class="preview-header">To</div> <?php p($mail->msgto) ?><br>
			<div class="preview-header">Subject</div> <?php p($mail->msgsubject) ?><br>
			<div class="hr"></div>

			<?php
			if ($encode == 'TEXT')
				echo '<pre>'.$body.'</pre>';
			else if ($body)
				echo $body;
			?>

			<div class="preview-attachments">
			<?php foreach($attachments as $a) { ?>
				<div class="preview-attachment"><?php p($a[2]) ?> (<?php echo round($a[1]/1024, 0) ?> KiB)</div>
			<?php } ?>
			</div>

			<div class="preview-headers">
			<?php foreach(explode("\n", $header) as $line) { ?>
				<pre class="indent"><?php echo $line; ?></pre>
			<?php } ?>
			</div>

			<?php if (count($mail->msgscore->item) > 0) { ?>
			<table class="list pad">
				<thead>
					<th>Scanner</th>
					<th>Result</th>
				</thead>
				<tbody>
				<?php foreach($mail->msgscore->item as $score) {
					list($num, $text) = explode("|", $score->second);
					echo '<tr>';
					switch ($score->first) {
						case 0;
							echo "<td>SpamAssassin</td><td>$num".($text != ""?" (".str_replace(",", ", ", $text).")":"")."</td>";
						break;
						case 1;
							$text = $text ?: "OK";
							echo "<td>Kaspersky</td><td>$text</td>";
						break;
						case 3;
							echo "<td>Commtouch</td><td>$num ($text)</td>";
						break;
						case 4;
							$text = $text ?: "OK";
							echo "<td>ClamAV</td><td>$text</td>";
						break;
					}
					echo "</tr>";
				} ?>
				</tbody>
			</table>
			<?php } ?>

			<form id="actionform" method="post" action="?page=preview">
				<input type="hidden" name="action" id="action" value="">
			</form>

<?php require_once('inc/footer.php'); ?>
