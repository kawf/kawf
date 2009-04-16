<?php
require_once("message.inc");

$msg = fetch_message($user, $mid, 'message,url,urltext,tid');

mark_thread_read($msg, $user);

header("Content-type: text/plain");
echo postprocess($msg);
?>
