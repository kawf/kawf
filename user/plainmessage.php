<?php
require_once("lib/YATT/YATT.class.php");
require_once("message.inc.php");

$forum = get_forum();
if (!$forum) {
  echo "Invalid forum\n";
  exit;
}

if(isset($forum['option']['LoginToRead']) and $forum['option']['LoginToRead']) {
  $user->req();
  if ($user->status != 'Active') {
    echo "Your account isn't validated\n";
    exit;
  }
}

$raw = isset($_REQUEST['raw']);

$msg = fetch_message($forum['fid'], $user, $mid, 'mid, ' . MESSAGE_PLAIN_FIELDS);

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
