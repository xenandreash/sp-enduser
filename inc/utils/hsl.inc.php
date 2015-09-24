<?php

function hsl_script()
{
	global $settings;
$str = '';
$str .=<<<'EOF'
if ($username == "sp-enduser" and $password == "badpassword") {
    $ok = false;

EOF;
if ($settings->getDisplayHistory()) {
$str .=<<<'EOF'
    if ($soapcall == "mailHistory") $ok = true;

EOF;
}
if ($settings->getDisplayQueue() || $settings->getDisplayQuarantine()) {
$str .=<<<'EOF'
    if ($soapcall == "mailQueue") $ok = true;
    if ($soapcall == "mailQueueDelete") $ok = true;
    if ($soapcall == "mailQueueRetry") $ok = true;
    if ($soapcall == "mailQueueBounce") $ok = true;

EOF;
}
if ($settings->getDisplayStats()) {
$str .=<<<'EOF'
    if ($soapcall == "graphFile") $ok = true;
    if ($soapcall == "statList") $ok = true;

EOF;
}
if ($settings->getDisplayTextlog()) {
$str .=<<<'EOF'
    if ($soapcall == "commandRun" and
        $soapargs["argv"][0] == "searchlog" and
        $soapargs["argv"][1] =~ "/^([0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12})(:[0-9]+)?$/")
            $ok = true;

EOF;
}
$str .=<<<'EOF'
    if ($soapcall == "commandRun" and
        $soapargs["argv"][0] == "previewmessage")
            $ok = true;
    if ($soapcall == "commandPoll") $ok = true;
    if ($soapcall == "commandStop") $ok = true;
    if ($soapcall == "fileRead" and
        substr($soapargs["file"], 0, 24) == "/storage/mail/processed/")
            $ok = true;
    if ($soapcall == "getSerial") $ok = true;
    if ($ok)
        Authenticate();

EOF;
$str .=<<<'EOF'
}
EOF;
	return $str;
}
