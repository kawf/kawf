#!/usr/bin/php -q
<?php

require_once("../config/setup.inc");

/* First setup the path */
$include_path = "$srcroot/include:$srcroot/config";
$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

include_once("$config.inc");
require_once("sql.inc");
// require_once("template.inc"); // REMOVED
require_once("user.inc");
require_once("textwrap.inc");
require_once("mailfrom.inc");

// REMOVED Template instantiation and set_file
// $tpl = new Template($template_dir, "comment");
// $tpl->set_file("mail", "mail/offtopic.tpl");

db_connect();

if(!ini_get('safe_mode'))
    set_time_limit(0);

$sth = db_query("select * from f_forums");
while ($f = $sth->fetch()) {
  $forums[$f['fid']] = $f;
}
$sth->closeCursor();

$sth = db_query("select * from f_offtopic where UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(tstamp) > 10 * 60");
while ($msg = $sth->fetch()) {
  $nuser = new User;
  $nuser->find_by_aid((int)$msg['aid']);

  // REMOVED Template set_var call
  /*
  $tpl->set_var(array(
    "EMAIL" => $nuser->email,
    "FORUM_SHORTNAME" => $forums[$msg['fid']]['shortname'],
    "MSG_MID" => $msg['mid'],
    "PHPVERSION" => phpversion(),
  ));
  */

  // REMOVED Template parse call - Set message to empty as template is missing
  // $e_message = $tpl->parse("MAIL", "mail");
  $e_message = ""; // Set to empty string or a placeholder text if needed

  // Only send email if message isn't empty (optional, prevents blank emails)
  if (!empty($e_message)) {
      $e_message = textwrap($e_message, 78, "\n");
      mailfrom("followup-" . $nuser->aid . "@" . $bounce_host,
        $nuser->email, $e_message);
  }

  unset($nuser);

  db_exec("delete from f_offtopic where fid = ? and mid = ?", array($msg['fid'], $msg['mid']));
}
$sth->closeCursor();

?>
