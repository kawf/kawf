<?php
/* Avoid reincluding the code */
if (!defined("_SQL_INC_")) {

define("_SQL_INC_", "1");

/* Open's up the DB with the admin user */
function sql_open_admin($db = "")
{
  global $sql_admin_username, $sql_admin_password;

  mysql_pconnect("localhost", $sql_admin_username, $sql_admin_password);
  if (empty($db))
    mysql_select_db("a4");
  else
    mysql_select_db($db);
}

/* Open's up the DB with the read/write user */
function sql_open_readwrite($db = "")
{
  global $sql_rw_username, $sql_rw_password;

  mysql_pconnect("localhost", $sql_rw_username, $sql_rw_password);
  if (empty($db))
    mysql_select_db("a4");
  else
    mysql_select_db($db);
}

/* Open's up the DB with the read user */
function sql_open_readonly($db = "")
{
  global $sql_ro_username, $sql_ro_password;

  mysql_pconnect("localhost", $sql_ro_username, $sql_ro_password);
  if (empty($db))
    mysql_select_db("a4");
  else
    mysql_select_db($db);
}

/* Useful function to print out a standard error message */
function sql_warn($sql)
{
  echo "<p>Error with SQL Query<br>\n";
  echo "<pre>$sql</pre>\n";
  echo "Error #", mysql_errno(), ": ", mysql_error(), "<br>\n";
}

function sql_error($sql, $noexit = 0)
{
  sql_warn($sql);
  if (!$noexit)
    exit;
}
}
?>
