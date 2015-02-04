<?php

/*
 * Don't invoke directly. Run as:
 * php cron.php.txt digestday
 */

if (!isset($_SERVER['argc']))
	die('this file can only be run from command line');

define('BASE', dirname(__FILE__).'/..');
require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

$limit = 10000; // Need limit, because of memory
//$restrict[] = 'quarantine=mailquarantine:1';

// Build query
$settings = Settings::Get();
if ($settings->getQuarantineFilter()) {
	$qf = array();
	foreach ($settings->getQuarantineFilter() as $q)
		$qf[] = 'quarantine='.$q;
	if (!empty($qf)) {
		$restrict[] = implode(' or ', $qf);
		$restrict[] = '&&';
	}
}
$restrict[] = 'action=QUARANTINE';
$restrict[] = 'time>'.strtotime('-24hour');
$real_search = implode(' ', $restrict);

// Initial settings
$timesort = array();
$tasks = array();
$total = 0;
$param = array();
$clients = array();
foreach ($settings->getNodes() as $n => $r) {
	$param[$n]['limit'] = $limit;
	$param[$n]['filter'] = $real_search;
	$param[$n]['offset'] = 0;
	$clients[$n] = soap_client($n);
}

function access_level_merge($a, $b)
{
	if (!isset($a)) return $b;
	if (!isset($b)) return $a;
	if (empty($a) || empty($b)) return array();
	return array_merge_recursive($a, $b);
}

// Perform actual requests
echo "Making query $real_search\n";
foreach ($settings->getNodes() as $n => $r) {
	$data = $clients[$n]->mailQueue($param[$n]);
	if (is_array($data->result->item)) foreach ($data->result->item as $item)
		$timesort[$item->msgts][] = array('id' => $n, 'type' => 'queue', 'data' => $item);
	$total += $data->totalHits;
}
krsort($timesort);
if (empty($timesort))
	die("No quarantined messages within one day\n");

$users = array();
foreach ($settings->getAuthSources() as $a) {
	// Send to statically configured users with e-mail address
	if ($a['type'] == 'account' && isset($a['email']))
		$users[$a['email']] = access_level_merge($users[$a['email']], $a['access']);
	// Send to LDAP users if a bind_dn is specified
	if ($a['type'] == 'ldap') {
		if (!isset($a['bind_dn']) || !isset($a['bind_password'])) {
			echo "LDAP source without bind_dn or bind_password, skipping\n";
			continue;
		}
		$ds = ldap_connect($a['uri']);
		if (!$ds) continue;
		if (is_array($a['options']))
			foreach ($a['options'] as $k => $v)
				ldap_set_option($ds, $k, $v);
		$bind = ldap_bind($ds, $a['bind_dn'], $a['bind_password']);
		if (!$bind) continue;
		ldap_set_option($ds, LDAP_OPT_SIZELIMIT, 10000); // Increase and/or sync with AD if getting "Sizelimit exceeded"
		if ($a['schema'] == 'msexchange') {
			$try_paged = false;
			if (function_exists('ldap_control_paged_result') &&
				ldap_get_option($ds, LDAP_OPT_PROTOCOL_VERSION, $version) &&
				$version >= 3)
				$try_paged = true;
			$cookie = '';
			do {
				if ($try_paged)
					ldap_control_paged_result($ds, 1000, true, $cookie);

				$rs = ldap_search($ds, $a['base_dn'], '(proxyAddresses=smtp:*)', array('proxyAddresses'));
				for ($entry = ldap_first_entry($ds, $rs); $entry; $entry = ldap_next_entry($ds, $entry)) {
					$aliases = array();
					foreach (ldap_get_values($ds, $entry, 'proxyAddresses') as $mail) {
						if (substr($mail, 0, 5) == 'SMTP:')
							array_unshift($aliases, strtolower(substr($mail, 5)));
						if (substr($mail, 0, 5) == 'smtp:')
							array_push($aliases, strtolower(substr($mail, 5)));
					}
					if (count($aliases))
						$users[$aliases[0]] = access_level_merge($users[$aliases[0]], array('mail' => $aliases));
				}

				if ($try_paged)
					ldap_control_paged_result_response($ds, $rs, $cookie);
				else
					break;
			} while($cookie !== null && $cookie != '');
		} else {
			$rs = ldap_search($ds, $a['base_dn'], '(mail=*)', array('mail'));
			for ($entry = ldap_first_entry($ds, $rs); $entry; $entry = ldap_next_entry($ds, $entry)) {
				$aliases = array();
				// Non-Exchange, assume 'mail' without prefix
				foreach (ldap_get_values($ds, $entry, 'mail') as $mail)
					if (is_string($mail))
						$aliases[] = strtolower($mail);
				if (count($aliases))
					$users[$aliases[0]] = access_level_merge($users[$aliases[0]], array('mail' => $aliases));
			}
		}
	}
}
// Send to everyone in quarantine, if enabled in settings
$allusers = array();
if ($settings->getDigestToAll())
	foreach ($timesort as $t)
		foreach ($t as $m)
			$allusers[$m['data']->msgto] = true;
foreach ($allusers as $email => $tmp)
	$users[$email] = access_level_merge($users[$email], array('mail' => array($email)));

$size = 500;
echo "Found ".count($users)." users\n";
foreach ($users as $email => $access) {
	$i = 0;
	$data = '<table><tr><th>Date</th><th>From</th><th>To</th><th>Subject</th><th></th></tr>';
	foreach ($timesort as $t) {
		if ($i > $size)
			break;
		foreach ($t as $m) {
			if ($i > $size)
				break;
			// Only show messages they have access to
			$match = false;
			if (count($access) == 0) // no restrictions
				$match = true;
			if (isset($access['mail']))
				foreach ($access['mail'] as $mail)
					if ($m['data']->msgto == $mail)
						$match = true;
			list($tobox, $todomain) = explode('@', $m['data']->msgto);
			if (isset($access['domain']))
				foreach ($access['domain'] as $domain)
					if ($todomain == $domain)
						$match = true;
			if (!$match)
				continue;
			// Make direct release link
			$extra = '';
			if ($settings->getDigestSecret()) {
				// Sign the "message"
				$time = time();
				$message = $m['id'].$m['data']->id.$time.$m['data']->msgid;
				$hash = hash_hmac('sha256', $message, $settings->getDigestSecret());
				$extra .= '<a href="'.$settings->getPublicURL().'/?page=digest&queueid='.$m['data']->id.
					'&node='.$m['id'].'&time='.$time.'&sign='.$hash.'">Release</a>';
			}
			$data .= '<tr><td>'.strftime('%F %T', $m['data']->msgts).'</td><td>'.$m['data']->msgfrom.'</td><td>'.$m['data']->msgto.'</td><td>'.$m['data']->msgsubject.'</td><td>'.$extra.'</td></tr>';
			$i++;
		}
	}
	if ($i == 0)
		continue;
	$data = '<p>You have '.$i.' message(s) received to your <a href="'.$settings->getPublicURL().'/?source=quarantine">quarantine</a> during the last 24 hours.</p>'.$data.'</table>';
	$data = chunk_split(base64_encode($data));
	echo "digest to $email with $i msgs\n";
	$headers = array();
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	$headers[] = 'Content-Transfer-Encoding: base64';
	mail2($email, "Quarantine digest, $i new messages", $data, $headers);
}
