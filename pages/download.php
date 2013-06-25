<?php
if(!defined('SP_ENDUSER')) die('File not included');

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
$mail = $queue->result->item[0];

header('Content-type: text/plain');
header('Content-Disposition: attachment; filename='.$mail->msgid.'.txt');

$file = $mail->msgpath;
$read = 10000;
$offset = 0;
try {
	while (true) {
		$result = $client->fileRead(array('file' => $file, 'offset' => $offset, 'size' => $read));
		echo $result->data;
		flush();
		if ($result->size < $read)
			break;
		$offset = $result->offset;
	}
} catch(SoapFault $f) {
	echo "Error: ".$f->faultstring;
}

?>
