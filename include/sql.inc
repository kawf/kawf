<?php

class SqlException extends Exception {}
class SqlDuplicateKey extends SqlException {}

function sql_open($db = "")
{
  global $sql_host, $sql_username, $sql_password;
  global $sql_open;

  if (isset($sql_open) && $sql_open == "$sql_host:$sql_username:$sql_password")
    return;

  if (!isset($sql_host))
    $host = "localhost";
  else
    $host = $sql_host;
  /* FIXME! use new mysql interface */
  if(!@mysql_connect($host, $sql_username, $sql_password)) {
    echo "<pre>Down for maintenance</pre>\n";
    exit;
  }

  $sql_open = "$sql_host:$sql_username:$sql_password";

  if (!empty($db))
    mysql_select_db($db);

  mysql_query('set session time_zone="+0:00"');
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

function sql_error($sql = "", $link = "")
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

/* Like sql_query(), but will raise an exception on error. */
function sql_execute($sql)
{
  $result = _sql_query($sql);
  if(!$result) {
    $errno = mysql_errno();
    $error = mysql_error();
    if($errno == 1062) {
      throw new SqlDuplicateKey($errno . ":" . $error);
    } else {
      throw new SqlException($errno . ":" . $error);
    }
  }
  return $result;
}

function sql_query1($sql)
{
  $result = sql_query($sql);
  if (!sql_num_rows($result))
    return null;

  list($var) = sql_fetch_row($result);

  sql_free_result($result);

  return $var;
}

function sql_querya($sql)
{
  $result = sql_query($sql);
  if (!sql_num_rows($result))
    return null;

  $array = sql_fetch_array($result);

  sql_free_result($result);

  return $array;
}

function sql_queryh($sql)
{
  $result = sql_query($sql);
  if (!sql_num_rows($result))
    return null;

  $hash = sql_fetch_assoc($result);

  sql_free_result($result);

  return $hash;
}

function sql_query1c($sql)
{
  $out = array();
  $result = sql_query($sql);
  if (!sql_num_rows($result))
    return null;

  while ($row = sql_fetch_array($result))
    $out[] = $row[0];

  sql_free_result($result);

  return $out;
}

function sql_free_result($result)
{
  mysql_free_result($result);
}

function sql_num_rows($result)
{
  return mysql_num_rows($result);
}

function sql_fetch_row($result)
{
  return mysql_fetch_row($result);
}

function sql_fetch_array($result)
{
  return mysql_fetch_array($result);
}

function sql_fetch_assoc($result)
{
  return mysql_fetch_assoc($result);
}

function sql_last_insert_id()
{
  return mysql_insert_id();
}

function sql_affected_rows()
{
  return mysql_affected_rows();
}

function sql_close()
{
  mysql_close();
}

function sql_escape($thing)
{
  if(is_null($thing)) {
    return "NULL";
  } elseif(is_int($thing) or is_float($thing)) {
    return "$thing";
  } else {
    return "'" . mysql_real_escape_string($thing) . "'";
  }
}

?>
