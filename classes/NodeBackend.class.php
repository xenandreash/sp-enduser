<?php

class NodeBackend extends Backend
{
	// TODO: Asynchronous calls!
	// TODO: Less repetition!
	
	private $nodes = null;
	
	public function __construct($nodes)
	{
		$this->nodes = $nodes;
	}
	
	// Node backends support everything
	public function supportsHistory() { return true; }
	public function supportsQueue() { return true; }
	public function supportsQuarantine() { return true; }
	
	public function loadMailHistory($search, $size, &$errors = array())
	{
		$timesort = array();
		
		foreach ($this->nodes as $n => &$c)
		{
			try
			{
				$params = array(
					'limit' => $size + 1,
					'filter' => $search,
					'offset' => 0
				);
				$data = $c->soap()->mailHistory($params);
				
				if (is_array($data->result->item))
					foreach ($data->result->item as $item)
						$timesort[$item->msgts0][] = array('id' => $n, 'type' => 'history', 'data' => $item);
			}
			catch (SoapFault $f)
			{
				$errors[] = $f->faultstring;
			}
		}
		
		return $timesort;
	}
	
	public function loadMailQueue($search, $size, &$errors = array())
	{
		foreach ($this->nodes as $n => &$c)
		{
			try
			{
				$params = array(
					'limit' => $size + 1,
					'filter' => $search,
					'offset' => 0
				);
				$data = $c->soap()->mailQueue($params);
				
				if (is_array($data->result->item))
					foreach ($data->result->item as $item)
						$timesort[$item->msgts0][] = array('id' => $n, 'type' => 'queue', 'data' => $item);
			}
			catch (SoapFault $f)
			{
				$errors[] = $f->faultstring;
			}
		}
		
		return $timesort;
	}
}
