$triggerurl = "http://end-user-url/api.php?api-key=badsecret";

function ScanBWList()
{
	global $triggerurl, $senderip, $sender, $recipient;
	$data = http($triggerurl . "&type=bwcheck&senderip=$1&sender=$2&recipient=$3",
			["timeout" => 10, "ssl_default_ca" => true],
			[$senderip, $sender, $recipient]);
	if ($data == "whitelist")
		return 0;
	if ($data == "blacklist")
		return 100;
	return 50;
}
