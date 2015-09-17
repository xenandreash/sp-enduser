<?php

// Pendling Async SOAP requests
$_soapRequests = array();
$_soapResponses = array();

function soap_dispatch()
{
	global $_soapRequests;
	global $_soapResponses;

	if (!in_array('curl', get_loaded_extensions())) {
		foreach ($_soapRequests as $id => $ch)
			$_soapResponses[$id] = true;
		$_soapRequests = array();
		return;
	}

	$mh = curl_multi_init();
	foreach ($_soapRequests as $ch)
		curl_multi_add_handle($mh, $ch);
	$active = null;
	do {
		$mrc = curl_multi_exec($mh, $active);
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	while ($active && $mrc == CURLM_OK) {
		if (curl_multi_select($mh) == -1) usleep(100000);
		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	}
	foreach ($_soapRequests as $id => $ch) {
		$_soapResponses[$id] = curl_multi_getcontent($ch);
		if ($_soapResponses[$id] === NULL)
			$_soapResponses[$id] = new SoapFault("HTTP", curl_error($ch));
		curl_multi_remove_handle($mh, $ch);
		curl_close($ch);
	}
	curl_multi_close($mh);
	$_soapRequests = array();
}
