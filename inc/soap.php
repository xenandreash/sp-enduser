<?php

$_soapRequests = array();
$_soapResponses = array();

class SoapClientAsync extends SoapClient {
	function __doRequest($request, $location, $action, $version) {
		global $_soapResponses;
		global $_soapRequests;

		if (!in_array('curl', get_loaded_extensions())) {
			$id = sha1($location.$request);
			if (isset($_soapResponses[$id])) {
				unset($_soapResponses[$id]);
				return parent::__doRequest($request, $location, $action, $version);
			}
			$_soapRequests[$id] = true;
			return "";
		}

		$id = sha1($location.$request);
		if (isset($_soapResponses[$id])) {
			$data = $_soapResponses[$id];
			unset($_soapResponses[$id]);
			if ($data instanceof SoapFault)
				throw $data;
			return $data;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $location);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->_connection_timeout ?: 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_USERPWD, $this->_login.':'.$this->_password);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$_soapRequests[$id] = $ch;
		return "";
	}
}

function soap_dispatch() {
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

?>
