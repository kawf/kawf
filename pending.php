<?php
/* Allows a user to see a status on account which is pending */

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  pending => 'pending.tpl',
  pending_row => 'pending_row.tpl',
  pending_row_text => 'pending_row_text.tpl'
));

$tpl->define_dynamic('error', 'pending');
$tpl->define_dynamic('form', 'pending');
$tpl->define_dynamic('table', 'pending');

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');

if (!isset($tracking)) {
  $tpl->clear_dynamic('error');
  $tpl->clear_dynamic('table');

  $tpl->parse(CONTENT, "pending");
  $tpl->FastPrint(CONTENT);

  exit;
}

$sql = "select * from pending where tracking = '" . addslashes($tracking) . "'";
$result = mysql_db_query($acctdb, $sql) or sql_error($sql);

if (!mysql_num_rows($result)) {
  $tpl->clear_dynamic('table');

  $tpl->parse(CONTENT, "pending");
  $tpl->FastPrint(CONTENT);

  exit;
}

$pending = mysql_fetch_array($result);

$sql = "select * from history where aid = '" . addslashes($pending['aid']) . "' order by date";
$result = mysql_db_query($acctdb, $sql) or sql_error($sql);

$i = 0;
while ($history = mysql_fetch_array($result)) {
  $color = ($i & 1) ? "#ffb0b0" : "#ff8080";
  $i++;

  $tpl->assign(TRTAGS, " bgcolor=\"$color\"");
  $tpl->assign(TYPE, $history['type']);
  $tpl->assign(DATE, $history['date']);
  $tpl->parse(ROWS, ".pending_row");

  if (!empty($history['message'])) {
    $message = preg_replace("/</", "&lt;", $history['message']);
    $message = preg_replace("/>/", "&gt;", $message);
    $message = preg_replace("/\n/", "<br>\n", $message);

    $tpl->assign(TDTAGS, " bgcolor=\"$color\"");
    $tpl->assign(MESSAGE, $message);
    $tpl->parse(ROWS, ".pending_row_text");
  }
}

$tpl->clear_dynamic('form');
$tpl->clear_dynamic('table');

$tpl->parse(CONTENT, "pending");
$tpl->FastPrint(CONTENT);
?>
