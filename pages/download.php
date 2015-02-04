<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/utils.php';

$node = intval($_GET['node']);
$id = intval($_GET['id']);

// Access permission
$mail = restrict_soap_mail('queue', $node, $id); // dies for security

$client = soap_client($node);
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
} catch (SoapFault $f) {
	echo "Error: ".$f->faultstring;
}
