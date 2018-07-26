$triggerurl = "http://end-user-url/api.php?api-key=badsecret";

function SpamSettings()
{
	global $triggerurl, $recipientdomain, $recipient;
	$data = cache [
				"ttl_function" => API_ttl,
				"update_function" => API_update,
				"namespace" => "SpamSettings",
			]
			http($triggerurl . "&type=spamsettings", ["timeout" => 10, "tls_default_ca" => true]);
	$list = json_decode($data);
	if (!is_array($list))
		return 0;
	foreach ($list as $item) {
		if ($item["access"] == $recipient)
			return $item["settings"];
		if ($item["access"] == $recipientdomain)
			$domain = $item["settings"];
		if ($item["access"] == "")
			$everyone = $item["settings"];
	}
	if (isset($domain)) return $domain;
	if (isset($everyone)) return $everyone;
	return ["level" => "medium"];
}

function API_ttl($new)
{
	if (is_array(json_decode($new)))
		return 300;
	return 60;
}

function API_update($old, $new)
{
	if (is_array(json_decode($new)))
		return $new;
	return $old;
}
