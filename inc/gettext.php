<?php

function prefered_language ($languages, $accept)
{
	// HTTP_ACCEPT_LANGUAGE is defined in
	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
	// pattern to find is therefore something like this:
	//    1#( language-range [ ";" "q" "=" qvalue ] )
	// where:
	//    language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
	//    qvalue         = ( "0" [ "." 0*3DIGIT ] )
	//            | ( "1" [ "." 0*3("0") ] )
	preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
				   "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
				   $accept, $hits, PREG_SET_ORDER);

	// default language (in case of no hits) is the first in the array
	$bestlang = $languages[0];
	$bestqval = 0;

	foreach ($hits as $arr) {
		// read data from the array of this hit
		$langprefix = strtolower ($arr[1]);
		if (!empty($arr[3])) {
			$langrange = strtolower ($arr[3]);
			$language = $langprefix . "-" . $langrange;
		}
		else $language = $langprefix;
		$qvalue = 1.0;
		if (!empty($arr[5])) $qvalue = floatval($arr[5]);

		// find q-maximal language
		if (in_array($language,$languages) && ($qvalue > $bestqval)) {
			$bestlang = $language;
			$bestqval = $qvalue;
		}
		// if no direct hit, try the prefix only but decrease q-value
		// by 10% (as http_negotiate_language does)
		else if (in_array($languageprefix,$languages) 
			&& (($qvalue*0.9) > $bestqval)) 
		{
			$bestlang = $languageprefix;
			$bestqval = $qvalue*0.9;
		}
	}
	return $bestlang;
}

$languages = array_merge(array('en-us'), array_map(function ($f) { return str_replace('_', '-', strtolower(basename($f))); }, glob('locale/*', GLOB_ONLYDIR)));
$prefered_language = prefered_language($languages, $_SERVER['HTTP_ACCEPT_LANGUAGE']);
$prefered_language = explode('-', $prefered_language);
$prefered_language = $prefered_language[0] . '_' . strtoupper($prefered_language[1]);

if ($prefered_language != 'en_US')
{
	setlocale(LC_MESSAGES, $prefered_language.'.UTF-8');
	bind_textdomain_codeset('messages', 'UTF-8');
	bindtextdomain('messages', 'locale/');
	textdomain('messages');
}
