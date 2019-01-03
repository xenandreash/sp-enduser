<?php

class ElasticsearchBackend extends Backend
{
	private $es = null;
	private $rotate;

	public function __construct($es)
	{
		$this->es = $es;
		$this->rotate = $this->es->getRotate();
		if (!in_array($this->es->getIndex().strftime($this->rotate, time()), Session::Get()->getElasticsearchIndices())) {
			try {
				$params = [
					'index' => $this->es->getIndex().'*'
				];
				Session::Get()->setElasticsearchIndices(array_keys($this->es->client()->indices()->get($params)));
			} catch (Exception $e) { die($e->getMessage()); }
		}
	}

	public function isValid() { return $this->client() != null; }

	public function supportsHistory() { return true; }

	public function loadMailHistory($search, $size, $param, $index_range, &$errors = array())
	{
		$results = [];
		$indices = [];

		$start = new DateTime($index_range['start']);
		if ($_SESSION['timezone'] < 0)
			$start->modify('-1 day');
		$end = new DateTime($index_range['end']);
		if ($_SESSION['timezone'] > 0)
			$end = $end->modify('+2 day');
		else
			$end = $end->modify('+1 day');
		$interval = new DateInterval('P1D');
		$daterange = new DatePeriod($start, $interval, $end);
		foreach ($daterange as $date) {
			$index = $this->es->getIndex().strftime($this->rotate, $date->getTimestamp());
			if ($this->validIndex($index))
				$indices[] = $index;
		}
		if (count($indices) < 1)
			return;
		$params = [
			'index' => implode(',', $indices),
			'type' => $this->es->getType(),
			'size' => $size + 1,
		];
		$params['body'] = [];
		$params['body']['sort'][] = ['receivedtime' => ['order' => 'desc']];
		$query_must = [];
		if ($q = hql_to_es($search)) {
			$query = [];
			$query['default_operator'] = 'AND';
			$query['query'] = $q;
			$query_must[] = ['query_string' => $query];
		}
		$range_start = new DateTime($index_range['start']);
		$range_end = new DateTime($index_range['end']);
		$range_end = $range_end->modify('+1 day');
		$offset = isset($param[0]['offset']) ? $param[0]['offset'] : ($range_end->getTimestamp() + $_SESSION['timezone'] * 60) * 1000;
		$query = [];
		$query['receivedtime']['lt'] = $offset;
		$query['receivedtime']['gte'] = ($range_start->getTimestamp() + $_SESSION['timezone'] * 60) * 1000;
		$query_must[] = ['range' => $query];

		$query_should = [];
		if ($restrict = $this->restrict_query())
			$query_should = $restrict;

		$query = [];
		if ($query_must && $query_should) {
			$query['bool']['must'] = $query_must;
			$query['bool']['must'][]['bool']['should'] = $query_should;
		} else if ($query_must) {
			$query['bool']['must'] = $query_must;
		} else if ($query_should) {
			$query['should'] = $query_should;
		}
		if ($query) {
			//		$params['body']['query']['bool']['minimum_should_match'] = 1;
			$params['body']['query'] = $query;
		}

		try {
			$response = $this->es->client()->search($params);
			if (isset($response['hits']['hits'])) {
				foreach ($response['hits']['hits'] as $m) {
					$mail = es_mail_parser($m);
					$results[$mail['data']->msgts0][] = $mail;
				}
			}
		} catch (Exception $e) {
			$errors[] = "Exception code: ".$e->getMessage();
		}

		return $results;
	}

	public function getMail($index, $id)
	{
		$result = null;
		$access = Session::Get()->getAccess();

		$params = [
			'index' => $index,
			'id' => $id,
			'type' => $this->es->getType()
		];
		try {
			$response = $this->es->client()->get($params);
			if ($response) {
				$mail = es_mail_parser($response)['data'];
				if (is_array($access['mail']) || is_array($access['domain']) || is_array($access['sasl'])) {
					$access_mail = $access_domain = $access_sasl = false;
					if (is_array($access['mail']) && in_array($mail->owner, $access['mail']))
						$access_mail = true;
					if (is_array($access['domain']) && in_array($mail->ownerdomain, $access['domain']))
						$access_domain = true;
					if (is_array($access['sasl']) && in_array($mail->saslusername, $access['sasl']))
						$access_sasl = true;
					if ($access_mail || $access_domain || $access_sasl)
						$result = $mail;
				} else {
					$result = $mail;
				}
			}
		} catch (Exception $e) {}

		return $result;
	}

	public function restrict_query()
	{
		$access = Session::Get()->getAccess();
		$filter = [];
		if (is_array($access['domain']))
			foreach ($access['domain'] as $domain)
				$filter[] = ['ownerdomain' => $domain];

		if (is_array($access['mail']))
			foreach ($access['mail'] as $mail)
				$filter[] = ['owner' => $mail];

		if (is_array($access['sasl']))
			foreach ($access['sasl'] as $sasl)
				$filter[] = ['saslusername' => $sasl];

		$restrict = null;
		foreach ($filter as $f)
			$restrict[] = ['term' => $f];

		return $restrict;
	}

	public function validIndex($index)
	{
		if (in_array($index, Session::Get()->getElasticsearchIndices()))
			return true;
		return false;
	}
}
