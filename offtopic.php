<?php

/* First setup the path */
$include_path = "$srcroot/kawf:$srcroot/kawf/user";
if (isset($include_append))
  $include_path .= ":" . $include_append;

$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

require_once("$config.inc");
require_once("sql.inc");

$tpl = new Template($template_dir, "comment");
$tpl->set_file("mail", "mail/offtopic.tpl");

sql_open($database);

set_time_limit(0);

$result = sql_query("select * from f_forums");
while ($f = sql_fetch_array($result)) {
  $forums[$f['fid']] = $f;
}

$result = sql_query("select * from f_offtopic where UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(tstamp) > 10 * 60");
while ($msg = sql_fetch_array($result)) {
  $nuser = new ForumUser;
  $nuser->find_by_aid((int)$msg['aid']);

  $tpl->set_var(array(
    "EMAIL" => $nuser->email,
    "FORUM_SHORTNAME" => $forums[$msg['fid']]['shortname'],
    "MSG_MID" => $msg['mid'],
    "PHPVERSION" => phpversion(),
  ));

  $e_message = $tpl->parse("MAIL", "mail");
  $e_message = textwrap($e_message, 78, "\n");

  mailfrom("followup-" . $nuser->aid . "@" . $bounce_host,
    $nuser->email, $e_message);

  unset($nuser);

  sql_query("delete from f_offtopic where fid = " . $msg['fid'] . " and mid = " . $msg['mid']);
}

?>
