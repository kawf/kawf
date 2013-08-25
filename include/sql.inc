<?php

class PDODuplicateKey extends RuntimeException {}

$DBH = null;

// Connect to the database specified by the globals:
// $sql_host, $database, $sql_username, $sql_password
// The database handle is the global $DBH.  The connection will throw an
// exception on any error in connect or any query.
function db_connect() {
  global $DBH, $sql_host, $database, $sql_username, $sql_password;

  $host = "localhost";
  if(isset($sql_host)) {
    $host = $sql_host;
  }

  try {
    // This is MySQL specific.  The correct way to deal with timezones would
    // be to run everything in UTC and do any adjustments in the code.
    $DBH = new PDO("mysql:host=$host;dbname=$database", $sql_username, $sql_password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "set session time_zone='+0:00'"
    ));
  } catch(PDOException $e) {
     echo "<pre>Down for maintenance</pre>\n";
     exit;
  }
}

// Prepare and execute a database query with arguments and return the statement
// handle.  For example:
// $sth = db_query("SELECT id, name, email FROM users WHERE id = ? and email = ?", array($id, $email));
// Will throw a PDODuplicateKey exception for an integrity constraint violation error, and PDOException
// for all other errors.
function db_query($sql, $args) {
  $sth = $DBH->prepare($sql);
  try {
    $sth->execute($args);
  } catch(PDOException $e) {
    if($e->getCode() == '23000') {
      // '23000' is actually integrity constraint violation, but in case
      // of MySQL it's the same as duplicate key exception.
      $ne = new PDODuplicateKey($e->getMessage(), $e->getCode(), $e);
      $ne->errorInfo = $e->errorInfo;
      throw $ne;
    }
    throw $e;
  }
  return $sth;
}

// Prepare and execute a database query and throw away the result.  Returns the
// number of rows affected for INSERT/UPDATE/DELETE statements.
function db_exec($sql, $args) {
  $sth = db_query($sql, $args);
  $affected = $sth->rowCount();
  $sth->closeCursor();
  return $affected;
}

// Prepare and execute a database query, fetch the first row and return it.
// The row is fetched using PDO::FETCH_BOTH, so it returns an array indexed by
// both column name and 0-indexed column number.  If the result set is empty,
// returns NULL.
function db_query_first($sql, $args) {
  $sth = db_query($sql, $args);
  $row = $sth->fetch(PDO::FETCH_BOTH);
  $sth->closeCursor();
  return $row ? $row : NULL;
}

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
  $result = sql_execute($sql);
  $row = sql_fetch_row($result);
  sql_free_result($result);

  return $row ? $row[0] : null;
}

function sql_querya($sql)
{
  $result = sql_execute($sql);
  $row = sql_fetch_array($result);
  sql_free_result($result);

  return $row ? $row : null;
}

function sql_queryh($sql)
{
  $result = sql_execute($sql);
  $row = sql_fetch_assoc($result);
  sql_free_result($result);

  return $row ? $row : null;
}

function sql_query1c($sql)
{
  $out = array();
  $result = sql_execute($sql);

  while ($row = sql_fetch_array($result))
    $out[] = $row[0];

  sql_free_result($result);

  return $out ? $out : null;
}

// Execute a query and throw away the result.  Useful for updates.
function sql_queryn($sql)
{
  $result = sql_execute($sql);
  sql_free_result($result);
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
