<?php
require_once("lib/YATT/YATT.class.php");
require_once("message.inc.php");

if(isset($forum['option']['LoginToRead']) and $forum['option']['LoginToRead']) {
  $user->req();
  if ($user->status != 'Active') {
    echo "Your account isn't validated\n";
    exit;
  }
}

$raw = isset($_REQUEST['raw']);

$msg = fetch_message($user, $mid, 'message,url,urltext,video,tid');

if ($raw) {
    header("Content-type: text/plain");
    echo $msg['message'];
    exit;
}

mark_thread_read($forum['fid'], $msg, $user);

$m=postprocess($msg);

$tpl = new YATT($template_dir, "plain-message.yatt");
$tpl->set("message", $m);
$tpl->parse("page");
print $tpl->output();
?>
