<?php

$COOKIE_FORUM = 1;
$COOKIE_ADS = 2;

function acct_createcookie($email, $type)
{
  sql_open_readwrite();

  if (!is_int($type))
    $type = $COOKIE_FORUM;

  switch ($type) {
  case 1:
    $name = "ForumAccount";
    $dir = "/forum";
    $column = "forumcookie";
    $query = "";
    break;
  case 2:
    $name = "AdsAccount";
    $dir = "/ads";
    $column = "adscookie";
    $query = " and (NOW() - adststamp) < ". 30 * 60;
    break;
  }

  $sql = "select $column from accounts where email = '".addslashes($email)."' and $column != ''" . $query;

  $result = mysql_db_query($database, $sql) or sql_error($sql);

  if (!mysql_num_rows($result)) {
    $cookie = md5($name . $email .microtime());

    $sql = "update accounts set $column='$cookie' where email='$email';";
    $result = mysql_db_query($database, $sql) or sql_error($sql);
  } else
    list($cookie) = mysql_fetch_row($result);

  $expire = time() + (60 * 60 * 24 * 365 * 5);	/* 5 years */
  SetCookie($name, $cookie, $expire, $dir . "/", ".audiworld.com");
}

unset($account);

if (isset($ForumAccount)) {
  $account = $ForumAccount;
  $column = "forumcookie";
}

if (isset($AdsAccount)) {
  $account = $AdsAccount;
  $column = "adscookie";
}

if (isset($account) && !empty($account)) {
  $sql = "select * from accounts where $column = '" . addslashes($account) . "' and $column != '' and status = 'Active'";

  $result = mysql_db_query($database, $sql) or sql_error($sql);

  if (mysql_num_rows($result) > 0) {
    $user = mysql_fetch_array($result);

    $capabilities = explode(",", $user['capabilities']);
    while (list(,$flag) = each($capabilities))
      $user["cap.$flag"] = "true";
    $preferences = explode(",", $user['preferences']);
    while (list(,$flag) = each($preferences))
      $user["prefs.$flag"] = "true";
  }
}

function forum_admin()
{
  global $user;

  return isset($user['cap.Delete']);
}

function forum_moderate()
{
  global $user;

  return isset($user['cap.Moderate']);
}

?>
