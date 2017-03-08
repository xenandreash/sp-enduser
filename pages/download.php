<?php
if (!defined('SP_ENDUSER')) die('File not included');

header('Content-type: text/plain');

if (Session::Get()->checkDisabledFeature('preview-mail-body'))
	die('Permission denied');

$id = preg_replace('/[^0-9]/', '', $_GET['id']);

$nodeBackend = new NodeBackend($settings->getNode($_GET['node']));
if ($_GET['type'] == 'archive')
	$mail = $nodeBackend->getMailInArchive("queueid=".$id, $errors);
else
	$mail = $nodeBackend->getMailInQueue("queueid=".$id, $errors);
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
