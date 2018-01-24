$triggerurl = "http://end-user-url/api.php?api-key=badsecret";

function ScanBWList()
{
	global $triggerurl, $senderip, $senderdomain, $sender, $recipientdomain, $recipient;
	$data = cache [
				"ttl_function" => API_ttl,
				"update_function" => API_update,
				"namespace" => "ScanBWList",
			]
			http($triggerurl . "&type=bwlist", ["timeout" => 10, "ssl_default_ca" => true]);
	$list = json_decode($data);
	if (!is_array($list))
		return 50;
	$blacklist = false;
	foreach ($list as $item) {
		if ($item["access"] == "" or $item["access"] == $recipientdomain or $item["access"] == $recipient) {
			if ($item["value"] == $senderip or $item["value"] == $senderdomain or $item["value"] == $sender) {
				if ($item["type"] == "whitelist")
					return 0;
				if ($item["type"] == "blacklist")
					$blacklist = true;
			}
			if ($item["value"][0] == "." and is_subdomain($senderdomain, $item["value"])) {
				if ($item["type"] == "whitelist")
					return 0;
				if ($item["type"] == "blacklist")
					$blacklist = true;
			}
		}
	}
	if ($blacklist)
		return 100;
	return 50;
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