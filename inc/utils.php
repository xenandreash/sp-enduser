<?php
// This should already be included, but reinclude it anyways just to be sure
// that the autoloader and settings are initialized properly
require_once BASE.'/inc/core.php';

// Functions are grouped into smaller files for navigability
require_once BASE.'/inc/utils/hql.inc.php';
require_once BASE.'/inc/utils/soap.inc.php';
require_once BASE.'/inc/utils/soap_async.inc.php';
require_once BASE.'/inc/utils/mail.inc.php';

function history_parse_scores($mail)
{
	$rpd = array();
	$rpd[0] = 'Unknown';
	$rpd[10] = 'Suspect';
	$rpd[40] = 'Valid bulk';
	$rpd[50] = 'Bulk';
	$rpd[100] = 'Spam';
	$ret = array();
	// SOAP score structure
	if (isset($mail->msgscore->item)) foreach ($mail->msgscore->item as $score) {
		list($num, $text) = explode('|', $score->second);
		if ($score->first == 0) {
			$ret['sa']['name'] = 'SpamAssassin';
			$ret['sa']['score'] = $num;
			$ret['sa']['text'] = str_replace(',', ', ', $text);
		}
		if ($score->first == 1) {
			$res = 'Ok';
			if ($text)
				$res = 'Virus';
			$ret['kav']['name'] = 'Sophos';
			$ret['kav']['score'] = $res;
			$ret['kav']['text'] = $text;
		}
		if ($score->first == 3 || $score->first == 5) {
			$ret['rpd']['name'] = 'CYREN';
			$ret['rpd']['score'] = $rpd[$num];
			$ret['rpd']['text'] = $text;
		}
		if ($score->first == 4) {
			$res = 'Ok';
			if ($text)
				$res = 'Virus';
			$ret['clam']['name'] = 'ClamAV';
			$ret['clam']['score'] = $res;
			$ret['clam']['text'] = $text;
		}
	}
	// Local (SQL) log scores, from searchable columns
	if (isset($mail->score_sa) && $mail->score_sa) {
		$ret['sa']['name'] = 'SpamAssassin';
		$ret['sa']['score'] = floatval($mail->score_sa);

	}
	if (isset($mail->score_rpd) && $mail->score_rpd !== null) {
		$ret['rpd']['name'] = 'CYREN';
		$ret['rpd']['score'] = $rpd[intval($mail->score_rpd)];
	}
	// Local (SQL) log scores, from JSON blob
	if (isset($mail->scores) && $scores = json_decode($mail->scores, true)) {
		if (isset($scores['rpd'])) {
			$ret['rpd']['name'] = 'CYREN';
			$ret['rpd']['text'] = $scores['rpd'];
		}
		if (is_array($scores['sa'])) {
			$ret['sa']['name'] = 'SpamAssassin';
			$sas = array();
			foreach ($scores['sa'] as $key => $value)
				$sas[] = $key.'='.$value;
			$ret['sa']['text'] = implode(', ', $sas);
		}
		if ($scores['kav'] !== null) {
			$ret['kav']['name'] = 'Sophos';
			$viruses = $scores['kav'];
			if (is_array($viruses)) {
				$ret['kav']['score'] = 'Virus';
				$ret['kav']['text'] = implode(', ', $viruses);
			} else $ret['kav']['score'] = 'Ok';
		}
		if ($scores['clam'] !== null) {
			$ret['clam']['name'] = 'ClamAV';
			$viruses = $scores['clam'];
			if (is_array($viruses)) {
				$ret['clam']['score'] = 'Virus';
				$ret['clam']['text'] = implode(', ', $viruses);
			} else $ret['clam']['score'] = 'Ok';
		}
		if ($scores['rpdav'] != '') {
			$ret['rpdav']['name'] = 'RPDAV';
			if ($scores['rpdav'] == 0)
				$ret['rpdav']['score'] = 'Ok';
			if ($scores['rpdav'] == 50)
				$ret['rpdav']['score'] = 'Suspect';
			if ($scores['rpdav'] == 100)
				$ret['rpdav']['score'] = 'Virus';
		}
	}
	return $ret;
}

function generate_random_password()
{
	if (function_exists('openssl_random_pseudo_bytes'))
		return bin2hex(openssl_random_pseudo_bytes(32));

	// The effective security of this is horrid, but unfortunately we can't
	// depend on OpenSSL being available on Windows
	$pass = '';
	$chars = '0123456789abcdef';
	srand((float) microtime() * 10000000);
	for ($i = 0; $i < 64; $i++)
		$pass .= $chars[rand(0, strlen($chars)-1)];
	return $pass;
}

function has_auth_database()
{
	$settings = Settings::Get();
	foreach ($settings->getAuthSources() as $a)
		if ($a['type'] == 'database')
			return true;
	return false;
}

/**
 * Merges two two-dimensional arrays together.
 *
 * For example, this code:
 *
 * $arr1 = array(
 *     'a' => array($a1, $a2),
 *     'b' => array($b1),
 * );
 * $arr2 = array(
 *     'a' => array($a3),
 *     'c' => array($c1),
 * );
 * $arr = merge_2d($arr1, $arr2);
 *
 * Would result in:
 *
 * $arr = array(
 *     'a' => array($a1, $a2, $a3),
 *     'b' => array($b1),
 *     'c' => array($c1),
 * );
 *
 * While array_merge would produce:
 *
 * $arr = array(
 *     'a' => array($a3),
 *     'b' => array($b1),
 *     'c' => array($c1),
 * );
 */
function merge_2d($a1, $a2)
{
	foreach ($a2 as $k => $v) {
		if (!isset($a1[$k])) {
			$a1[$k] = $v;
		} else {
			$a1[$k] = array_merge($a1[$k], $v);
		}
	}

	return $a1;
}

function format_size($size)
{
	$base = log($size, 1024);
	$suffixes = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
	return round(pow(1024, $base - floor($base)), 0) . ' ' . $suffixes[floor($base)];
}

function password_policy($password, &$error)
{
	if (strlen($password) < 6) {
		$error = 'The password must be at least 6 characters long.';
		return false;
	}
	return true;
}

/**
 * Crossplatform strftime, because PHP for some reason thinks it's a good idea
 * to have platform-specific syntax for functions in a scripting language, and
 * instead of fixing this, they DOCUMENT A WORKAROUND.
 */
function strftime2($timestamp, $format)
{
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		$format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
	return strftime($format, $timestamp != NULL ? $timestamp : time());
}

function emptyspace($str)
{
	if ($str == '')
		return '<br>'; // XXX: empty table-cell hack
	return $str;
}

function extract_domain($address)
{
	return strpos($address, '@') !== false ? substr($address, strrpos($address, '@') + 1) : $address;
}

function sanitize_domain($domain)
{
	$d = preg_replace('/[^a-z0-9\.\-]/i', '', $domain);
	if ($d != $domain)
		$d .= '--'.substr(sha1($domain), 0, 8);
	else
		$d = $domain;

	return $d;
}

function es_mail_parser($m) {
	$mail = [];
	$mail['index'] = $m['_index'];
	$mail['type'] = 'es';// $m['_type'];
	$mail['receivedtime'] = $m['_source']['receivedtime'];
	$mail['data'] = (object) [
		'id' => $m['_id'],
		'owner' => $m['_source']['owner'],
		'ownerdomain' => $m['_source']['ownerdomain'],
		'msgid' => $m['_source']['messageid'],
		'msgaction' => $m['_source']['action'],
		'msglistener' => $m['_source']['serverid'],
		'msgtransport' => $m['_source']['transportid'],
		'msgsasl' => $m['_source']['saslusername'],
		'msgfromserver' => $m['_source']['senderip'],
		'msgfrom' => $m['_source']['sender'],
		'msgfromdomain' => $m['_source']['senderdomain'],
		'msgto' => $m['_source']['recipient'],
		'msgtodomain' => $m['_source']['recipientdomain'],
		'msgsubject' => $m['_source']['subject'],
		'msgsize' => $m['_source']['size'],
		'msgdescription' => $m['_source']['errormsg'],
		'msgactionid' => $m['_source']['actionid'],
		'msgts0' => (int)substr($m['_source']['receivedtime'], 0, -3),
		'serialno' => $m['_source']['serial'],
		'score_rpd' => $m['_source']['score_rpd'],
		'score_sa' => $m['_source']['scores']['sa'],
		'scores' => json_encode([
			'rpd' => $m['_source']['score_rpd_refid'],
			'rpdav' => $m['_source']['scores']['rpdav'],
			'sa' => $m['_source']['scores']['sa_rules'],
			'kav' => $m['_source']['scores']['kav'],
			'clam' => $m['_source']['scores']['clam']
		])
	];
	return $mail;
}
