<?php
if (!defined('SP_ENDUSER')) die('File not included');

header('Content-type: text/plain');

if (Session::Get()->checkDisabledFeature('preview-mail-body'))
	die('Permission denied');

if ($_GET['original'] == '1' && Session::Get()->checkDisabledFeature('preview-mail-body-original'))
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

$filename = $mail->msgid;
if ($_GET['original'] == '1')
	$filename .= '_org';

header('Content-Disposition: attachment; filename='.$filename.'.txt');

$file = $mail->id;
$read = 10000;
$offset = 0;
try {
	while (true) {
		$result = $client->mailQueueDownload(array('id' => $file, 'offset' => $offset, 'size' => $read, 'original' => $_GET['original'] == '1'));

		$data = base64_decode($result->data);
		echo $data;
		flush();

		$size = strlen($data);
		if ($size < $read)
			break;
		$offset += $size;
	}
} catch (SoapFault $f) {
	echo "Error: ".$f->faultstring;
}
