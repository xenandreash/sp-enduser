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
		return $m['data']->msgdescription;
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
		return $m['data']->msgdescription;
}
