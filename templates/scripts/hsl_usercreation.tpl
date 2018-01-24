$triggerurl = "http://end-user-url/api.php?api-key=badsecret";

function Quarantine(...$args)
{
	global $triggerurl, $recipient;
	$r = isset($args[1]["recipient"]) ? $args[1]["recipient"] : $recipient;
	http($triggerurl . "&type=trigger&recipient=$1", ["timeout" => 10, "background" => true, "background_retry_count" => 1, "ssl_default_ca" => true], [$r]);
	return builtin Quarantine(...$args);
}