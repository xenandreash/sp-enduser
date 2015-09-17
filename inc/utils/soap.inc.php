<?php

function soap_client($n, $async = false, $username = null, $password = null)
{
	$settings = Settings::Get();
	$r = $settings->getNode($n);
	if (!$r)
		throw new Exception("Node not configured");
	
	return $r->soap($async, $username, $password);
}

function soap_exec($argv, $c)
{
	$data = '';
	try {
		$id = $c->commandRun(array('argv' => $argv, 'cols' => 80, 'rows' => 24))->result;
		do {
			$result = $c->commandPoll(array('commandid' => $id))->result;
			if ($result && @$result->item)
				$data .= implode("", $result->item);
		} while (true);
	} catch (SoapFault $f) {
		if (!$id)
			return false;
	}
	return $data;
}
