$triggerurl = "http://end-user-url/api.php?api-key=badsecret";

$logdata = [
	"serialno" => serial(),
	"msgid" => $messageid,
	"msglistener" => $serverid,
	"msgsasl" => $saslusername,
	"msgsubject" => GetHeader("subject"),
	"msgsize" => MIME("0")->getSize(),
	"msgts0" => time()
];

function sendlog($action, $desc) {
	global $triggerurl, $logdata, $messageid, $actionid, $recipient, $transportid, $senderip, $sender;
	$logdata += [
		"msgaction" => $action,
		"msgdescription" => $desc,
		"msgts" => timelocal(),
		"msgactionid" => $actionid,
		"owner" => $recipient,
		"msgtransport" => $transportid,
		"msgfromserver" => $senderip,
		"msgfrom" => $sender,
		"msgto" => $recipient
	];
	http($triggerurl . "&type=log",
		["timeout" => 10, "background" => true, "background_hash" => hash($messageid), "background_retry_count" => 1, "ssl_default_ca" => true], [], $logdata);
}
function Reject(...$args) {
	$msg = isset($args[0]) ? $args[0] : "";
	sendlog("REJECT", $msg);
	builtin Reject(...$args);
}
function Deliver(...$args) {
	sendlog("QUEUE", "");
	builtin Deliver(...$args);
}
function Defer(...$args) {
	$msg = isset($args[0]) ? $args[0] : "";
	sendlog("DEFER", $msg);
	builtin Defer(...$args);
}
function ScanRPD(...$args) {
	global $logdata;
	$logdata += [ "score_rpd" => builtin ScanRPD(), "score_rpd_refid" => builtin ScanRPD([ "refid" => true ]) ];
	return builtin ScanRPD(...$args);
}
function ScanSA(...$args) {
	global $logdata;
	$logdata += [ "score_sa" => builtin ScanSA(), "score_sa_rules" => builtin ScanSA([ "rules" => true ]) ];
	return builtin ScanSA(...$args);
}
function ScanKAV(...$args) {
	global $logdata;
	$logdata += [ "score_kav" => builtin ScanKAV() ? : "" ];
	return builtin ScanKAV(...$args);
}
function ScanCLAM(...$args) {
	global $logdata;
	$logdata += [ "score_clam" => builtin ScanCLAM() ? : "" ];
	return builtin ScanCLAM(...$args);
}
function ScanRPDAV(...$args) {
	global $logdata;
	$logdata += [ "score_rpdav" => builtin ScanRPDAV() ];
	return builtin ScanRPDAV(...$args);
}
function Quarantine(...$args) {
	$msg = isset($args[1]["reason"]) ? $args[1]["reason"] : "";
	sendlog("QUARANTINE", $msg);
	builtin Quarantine(...$args);
}