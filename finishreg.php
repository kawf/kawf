<?php

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  finishreg => 'finishreg.tpl',
  prefform => 'prefform.tpl'
));

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');

$tpl->define_dynamic('error', 'prefform');

$tpl->define_dynamic('unknown', 'finishreg');
$tpl->define_dynamic('success', 'finishreg');

$tpl->assign(PAGE, $SCRIPT_NAME . $PATH_INFO);
$tpl->assign(TITLE, "Finish Registration");

sql_open_readwrite();

$sql = "select * from pending where cookie = '" . addslashes($cookie) . "'";
$result = mysql_db_query('accounts', $sql) or sql_error($sql);

if (mysql_num_rows($result)) {
  $pending = mysql_fetch_array($result);

  $sql = "select * from accounts where aid = " . $pending['aid'];
  $result = mysql_db_query('a4', $sql) or sql_error($sql);

  if (mysql_num_rows($result)) {
    $user = mysql_fetch_array($result);

    $sql = "delete from pending where cookie = '" . addslashes($cookie) . "'";
    mysql_db_query('accounts', $sql) or sql_error($sql);

    $sql = "insert into history ( aid, type, date ) values ( " . $user['aid'] . ", 'Finish Registration', NOW() )";
    mysql_db_query('accounts', $sql) or sql_error($sql);

    /* Need to set it before we send anything */
    setcookie("ForumAccount", $user['cookie'], time() + (60 * 60 * 24 * 365 * 5), "$urlroot/", $cookiedom);
  }
}

if (!isset($user)) {
  $tpl->assign(COOKIE, $cookie);
  $tpl->clear_dynamic('success');
} else {
  $text = "Thank you for registering at the AudiWorld forums, to complete your registration please select a password and peferences of your choice.";
  $tpl->assign(TEXT, $text);

  $tpl->assign(SIGNATURE, stripslashes($user['signature']));
  $tpl->assign(THREADSPERPAGE, $user['threadsperpage']);

  $tpl->assign(array(
    'SHOWMODERATED' => '',
    'COLLAPSED' => '',
    'SECRETEMAIL' => '',
    'SIMPLEHTML' => '',
    'FLATTHREAD' => '',
    'AUTOTRACK' => '',
    'HIDESIGNATURES' => '',
    'AUTOUPDATETRACKING' => ''
  ));

  $tpl->clear_dynamic('error');
  $tpl->clear_dynamic('unknown');
  $tpl->parse(PREF, 'prefform');
}

$tpl->parse(CONTENT, "finishreg");
$tpl->FastPrint(CONTENT);
?>
