<?php
require_once("template.inc");
require_once("message.inc");

$raw = isset($_REQUEST['raw']);

$msg = fetch_message($user, $mid, 'message,url,urltext,video,tid');

if ($raw) {
    header("Content-type: text/plain");
    echo $msg['message'];
    exit;
}

mark_thread_read($msg, $user);

$m=postprocess($msg);

$tpl = new Template($template_dir, "comment");
$tpl->set_file("plain", "plain-message.tpl");
$tpl->set_var("MSG_MESSAGE", $m);

/* no header or footer or anything, just print the message */
$tpl->pparse("MSG_MESSAGE", "plain");
?>
