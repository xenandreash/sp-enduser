<?php

class SoapClientAsync extends SoapClient {
	function __doRequest($request, $location, $action, $version, $one_way = 0) {
		global $_soapResponses;
		global $_soapRequests;

		if (!in_array('curl', get_loaded_extensions())) {
			$id = sha1($location.$request);
			if (isset($_soapResponses[$id])) {
				unset($_soapResponses[$id]);
				return parent::__doRequest($request, $location, $action, $version, $one_way);
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
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->_connection_timeout ?: 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_USERPWD, $this->_login.':'.$this->_password);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$_soapRequests[$id] = $ch;
		return "";
	}
}
