<?php

/**
 * Crossplatform strftime, because PHP for some reason thinks it's a good idea
 * to have platform-specific syntax for functions in a scripting language, and
 * instead of fixing this, they DOCUMENT A WORKAROUND.
 */
function strftime2($timestamp = NULL, $format)
{
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		$format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
	return strftime($format, $timestamp != NULL ? $timestamp : time());
}
