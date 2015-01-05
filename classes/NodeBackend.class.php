<?php

class NodeBackend extends Backend
{
	private $nodes = array();
	
	public function __construct($nodes)
	{
		$this->nodes = $nodes;
	}
	
	
	
	// A node backend is valid if it has at least one node
	public function isValid() { return count($this->nodes) > 0; }
	
	// Node backends support everything
	public function supportsHistory() { return true; }
	public function supportsQueue() { return true; }
	public function supportsQuarantine() { return true; }
	
	
	
	/**
	 * Send an asynchronous SOAP request to all nodes.
	 */
	public function soapCall($fname, $args, &$errors = array())
	{
		// Create an async SOAP client and stage an API call, note that
		// SoapFault exceptions can be thrown by either this call or the
		// retrieving one, so we have to handle either case.
		$clients = array();
		foreach ($this->nodes as $n => &$node)
		{
			$client = null;
			try {
				$client = $node->soap(true);
				$clients[$n] = $client;
			} catch (SoapFault $f) {
				// Don't explode if we can't connect
				$errors[] = $f->faultstring;
				continue;
			}
			
			try {
				call_user_func(array($client, $fname), $args);
			} catch (SoapFault $f) {
				$errors[] = $f->faultstring;
			}
		}
		
		// Dispatch all pending calls; if the curl extension is available, this
		// will be asynchronous, and will only take as long as the slowest node
		soap_dispatch();
		
		// Run the API call again to retrieve the resulting data
		$results = array();
		foreach ($clients as $n => &$c)
		{
			try {
				$results[$n] = call_user_func(array($clients[$n], $fname), $args);
			} catch (SoapFault $f) {
				$errors[] = $f->faultstring;
			}
		}
		
		return $results;
	}
	
	
	
	public function loadMailHistory($search, $size, $param, &$errors = array())
	{
		$params = array(
			'limit' => $size + 1,
			'filter' => $search,
			'offset' => $param[1]['offset']
		);
		$results = $this->soapCall('mailHistory', $params, $errors);
		
		$timesort = array();
		foreach ($results as $n => $data)
			if (is_array($data->result->item))
				foreach ($data->result->item as $item)
					$timesort[$item->msgts0][] = array('id' => $n, 'type' => 'history', 'data' => $item);
		
		return $timesort;
	}
	
	public function loadMailQueue($search, $size, $param, &$errors = array())
	{
		$params = array(
			'limit' => $size + 1,
			'filter' => $search,
			'offset' => $param[1]['offset']
		);
		$results = $this->soapCall('mailQueue', $params, $errors);
		
		$timesort = array();
		foreach ($results as $n => $data)
			if (is_array($data->result->item))
				foreach ($data->result->item as $item)
					$timesort[$item->msgts0][] = array('id' => $n, 'type' => 'queue', 'data' => $item);
		
		return $timesort;
	}
}
