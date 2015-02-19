<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/utils.php';

header('Content-type: text/plain');

$nodeBackend = new NodeBackend(array_slice($settings->getNodes(), $_GET['node'], 1));
$mail = $nodeBackend->getMailInQueue("queueid=".$_GET['id'], $errors);
if (!$mail)
	die('No mail found');
$client = $nodeBackend->soap();

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
} catch (SoapFault $f) {
	echo "Error: ".$f->faultstring;
}
