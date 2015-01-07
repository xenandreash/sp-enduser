<?php

function p($str) {
	echo htmlspecialchars($str);
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
