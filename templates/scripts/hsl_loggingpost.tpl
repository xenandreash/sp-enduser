$triggerurl = "http://end-user-url/api.php?api-key=badsecret";

$logdata = [
	"serialno" => serial(),
	"msgid" => $messageid,
	"msgactionid" => $actionid,
	"msgdescription" => $errormsg,
	"msgaction" => $action,
	"predelivery" => isset($context),
	"msgts0" => time()
];

if ($retry > 0) $logdata["msgdescription"] .= " (retry $retry/$retries)"; 
http($triggerurl . "&type=logupdate",
	["timeout" => 10, "background" => true, "background_hash" => hash($messageid), "background_retry_count" => 1, "ssl_default_ca" => true], [], $logdata); 