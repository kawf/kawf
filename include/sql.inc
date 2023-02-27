<?php

class PDODuplicateKey extends RuntimeException {}

class SQLAuth {
    public $hostname;
    public $database;
    public $username;
    public $password;

    public function __construct($host, $db, $user, $pass) {
	$this->hostname=$host;
	$this->database=$db;
	$this->username=$user;
	$this->password=$pass;
    }
}

$DBH = null;

// DO NOT USE THIS FUNCTION DIRECTLY
//
// Use db_connect() below to connect to the database.
function _db_connect($sql_auth) {
  global $DBH;
  try {
    $host = $sql_auth->hostname;
    $db = $sql_auth->database;
    $user = $sql_auth->username;
    $pass = $sql_auth->password;
    // This is MySQL specific.  The correct way to deal with timezones would
    // be to run everything in UTC and do any adjustments in the code.
    $DBH = new PDO("mysql:host=$host;dbname=$db", $user, $pass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "set session time_zone='+0:00'"
    ));
  } catch(PDOException $e) {
     // echo "<!-- $e -->\n";
     echo "<pre>Down for maintenance</pre>\n";
     exit;
  }
}

// Connect to the database specified by $sql_auth or the globals:
// $sql_host, $database, $sql_username, $sql_password
// The database handle is the global $DBH.  The connection will throw an
// exception on any error in connect or any query.
function db_connect($sql_auth=null) {
  global $DBH, $down_for_maint;

  if ($down_for_maint) {
    echo "The forums are currently undergoing maintenance, please try back in a couple of minutes\n";
    exit;
  }

  if (!isset($sql_auth)) {
    global $sql_host, $database, $sql_username, $sql_password;
    $sql_auth = new SQLAuth('localhost', $database, $sql_username, $sql_password);
    if(isset($sql_host)) {
      $sql_auth->hostname = $sql_host;
    }
  }

  if(!$DBH) {
    _db_connect($sql_auth);
  }
}

// Prepare and execute a database query with arguments and return the statement
// handle.  For example:
// $sth = db_query("SELECT id, name, email FROM users WHERE id = ? and email = ?", array($id, $email));
// Will throw a PDODuplicateKey exception for an integrity constraint violation error, and PDOException
// for all other errors.
function db_query($sql, $args=array()) {
  global $DBH;
  $sth = $DBH->prepare($sql);
  try {
    $sth->execute($args);
  } catch(PDOException $e) {
    //error_log(sprintf("query: '%s', args: [%s]", $sql, implode(',', $args)));
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
function db_exec($sql, $args=array()) {
  $sth = db_query($sql, $args);
  $affected = $sth->rowCount();
  $sth->closeCursor();
  return $affected;
}

// Prepare and execute a database query, fetch the first row and return it.
// The row is fetched using PDO::FETCH_BOTH, so it returns an array indexed by
// both column name and 0-indexed column number.  If the result set is empty,
// returns NULL.
function db_query_first($sql, $args=array()) {
  $sth = db_query($sql, $args);
  $row = $sth->fetch(PDO::FETCH_BOTH);
  $sth->closeCursor();
  return $row ? $row : NULL;
}

function db_last_insert_id() {
  global $DBH;
  return $DBH->lastInsertId();
}

?>
