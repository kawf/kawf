<?php
/* Avoid reincluding the code */
if (defined("__SQL_INC__"))
  return;

define("__SQL_INC__", "1");

function sql_open($db = "")
{
  global $sql_host, $sql_username, $sql_password;
  global $sql_open;

  if (isset($sql_open) && $sql_open == "$sql_hostname:$sql_username:$sql_password")
    return;

  if (!isset($sql_host))
    $host = "localhost";
  else
    $host = $sql_host;

  mysql_connect($host, $sql_username, $sql_password) or
    sql_error("mysql_pconnect");

  $sql_open = "$sql_hostname:$sql_username:$sql_password";

  if (!empty($db))
    sql_select_db($db);
}

function sql_select_db($db)
{
  if (!empty($db))
    mysql_select_db($db);
}

/* Useful function to print out a standard error message */
function sql_warn($sql, $link = "")
{
  echo "<p>Error with SQL Query<br>\n";
  echo "<pre>$sql</pre>\n";
  if (!empty($link))
    echo "Error #", mysql_errno($link), ": ", mysql_error($link), "<br>\n";
  else
    echo "Error #", mysql_errno(), ": ", mysql_error(), "<br>\n";
}

function sql_error($sql, $link = "")
{
  sql_warn($sql, $link);
  exit;
}

function _sql_query($sql)
{
  return mysql_query($sql);
}

function sql_query($sql)
{
  $result = _sql_query($sql) or sql_error($sql);
  return $result;
}

function sql_query1($sql)
{
  $result = sql_query($sql);
  if (!mysql_num_rows($result))
    return null;

  list($var) = mysql_fetch_row($result);

  mysql_free_result($result);

  return $var;
}

function sql_querya($sql)
{
  $result = sql_query($sql);
  if (!mysql_num_rows($result))
    return null;

  $array = mysql_fetch_array($result);

  sql_free_result($result);

  return $array;
}

function sql_free_result($result)
{
  mysql_free_result($result);
}

function sql_num_rows($result)
{
  return mysql_num_rows($result);
}

function sql_fetch_array($result)
{
  return mysql_fetch_array($result);
}

function sql_last_insert_id()
{
  return mysql_insert_id();
}

function sql_close()
{
  mysql_close();
}
?>
