<?php
require_once("template.inc");
require_once("message.inc");

$msg = fetch_message($user, $mid, 'message,url,urltext,tid');

mark_thread_read($msg, $user);

header("Content-type: text/plain");

$msg=postprocess($msg);

$tpl = new Template($template_dir, "comment");
$tpl->set_file("plain", "plain-message.tpl");
$tpl->set_var("MSG_MESSAGE", postprocess($msg));
$tpl->pparse("MSG_MESSAGE", "plain");
?>
