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
