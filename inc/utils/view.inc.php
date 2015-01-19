<?php

/**
 * Safely prints a value.
 * 
 * The second parameter is a default, which is assumed to be trusted, and not
 * escaped. To fall back to an untrusted value, use something like:
 * 
 *     <?php p($var) or p($default) ?>
 */
function p($str, $def = "") {
	echo $str ? htmlspecialchars($str) : $def;
	return (bool)$str;
}

function p_select($name, $selected, $options, $extra = "") {
	echo '<select id="'.$name.'" name="'.$name.'" '.$extra.'>';
	foreach ($options as $value => $label) {
		$extra = '';
		if ((string)$value == $selected)
			$extra = ' selected';
		echo '<option value="'.$value.'"'.$extra.'>'.$label.'</option>';
	}
	echo '</select>';
}

/**
 * Parses an SMTP status string, returning an array of the status message and
 * the extended description.
 * 
 * @param $part If given, the index of the array to return alone (0 or 1)
 */
function smtp_status_parse($status, $part = NULL) {
	$parts0 = explode(' ', $status, 2);
	$parts1 = explode(':', $parts0[1], 2);
	$parts = array($parts1[0], ucfirst(trim($parts1[1])));
	return $part !== NULL ? $parts[$part] : $parts;
}

/**
 * Returns a short status string for a message, such as "Ok" or
 * "In queue (retry 4)".
 */
function short_msg_status($m) {
	if ($m['data']->msgaction == 'QUARANTINE')
		return "Quarantine";
	else if ($m['type'] == 'queue' && $m['data']->msgaction == 'DELIVER')
		return "In queue (retry ".$m['data']->msgretries.")";
	else
		return smtp_status_parse($m['data']->msgdescription, 0);
}

/**
 * Returns a long status string for a message, such as
 * "Could not connect to [10.2.0.12]:25"
 */
function long_msg_status($m) {
	if ($m['type'] == 'queue' && $m['data']->msgaction == 'DELIVER')
		return "Retry ".$m['data']->msgretries;
	else
		return smtp_status_parse($m['data']->msgdescription, 1) ?: $m['data']->msgdescription;
}
