<?php

/**
 * Prints a trusted value. Use p() for user input.
 * Returns whether or not anything was printed.
 */
function pp($str)
{
	echo $str;
	return $str !== "";
}

/**
 * Safely prints a value, with HTML escaped. Use this unless you need to print
 * HTML, and you're really sure that said HTML does not contain user input.
 * 
 * Returns whether or not anything was printed, meaning you can do things like:
 * 
 *     <?php p($var) or p($default) ?>
 */
function p($str)
{
	return pp(htmlspecialchars($str));
}

/**
 * Crossplatform strftime, because PHP for some reason thinks it's a good idea
 * to have platform-specific syntax for functions in a scripting language, and
 * instead of fixing this, they DOCUMENT A WORKAROUND.
 */
function strftime2($format, $timestamp = NULL)
{
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		$format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
	return strftime($format, $timestamp != NULL ? $timestamp : time());
}

function p_select($name, $selected, $options, $extra = "")
{
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
 * Parses a status string, returning an array of the status message and
 * the extended description. Can handle either SMTP status lines or freeform
 * strings where the first word is the action.
 * 
 * @param $part If given, the index of the array to return alone (0 or 1)
 */
function parse_status($status, $part = NULL)
{
	// This needs a stricter implementation, and unit tests around it
	/*$parts0 = explode(' ', $status, 2);
	$parts1 = explode(':', $parts0[1], 2);
	
	$parts = array();
	if (count($parts1) != 2)
		$parts = array($parts0[0], $status);
	else
		$parts = array($parts1[0], ucfirst(trim($parts1[1])));*/
	
	$parts = array($status, "");
	return $part !== NULL ? $parts[$part] : $parts;
}

/**
 * Returns a short status string for a message, such as "Ok" or
 * "In queue".
 */
function short_msg_status($m)
{
	if ($m['data']->msgaction == 'QUARANTINE')
		return "Quarantine";
	else if ($m['type'] == 'queue' && $m['data']->msgaction == 'DELIVER')
		return "In queue";
	else
		return parse_status($m['data']->msgdescription, 0);
}

/**
 * Returns a long status string for a message, such as
 * "Could not connect to [10.2.0.12]:25"
 */
function long_msg_status($m)
{
	if ($m['type'] == 'queue' && $m['data']->msgaction == 'DELIVER')
		return "Retry ".$m['data']->msgretries;
	else
		return parse_status($m['data']->msgdescription, 1) ?: $m['data']->msgdescription;
}
