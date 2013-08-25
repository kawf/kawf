<?php

class PDODuplicateKeyException extends RuntimeException {}

class Database {
  protected static $dbh;

  public static function connect() {
    global $sql_host, $database, $sql_username, $sql_password;

    $host = "localhost";
    if(isset($sql_host)) {
      $host = $sql_host;
    }

    try {
      // This is MySQL specific.  The correct way to deal with timezones would
      // be to run everything in UTC and do any adjustments in the code.
      static::$dbh = new PDO("mysql:host=$host;dbname=$database", $sql_username, $sql_password, array(
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::MYSQL_ATTR_INIT_COMMAND => "set session time_zone='+0:00'"
      ));
    } catch(PDOException $e) {
       echo "<pre>Down for maintenance</pre>\n";
       exit;
    }
  }

  public static function query($sql, $args) {
    $sth = static::$dbh->prepare($sql);
    try {
      $sth->execute($args);
    } catch(PDOException $e) {
      if($e->getCode() == '23000') {
        // '23000' is actually integrity constraint violation, but in case
        // of MySQL it's the same as duplicate key exception.
        $ne = new PDODuplicateKeyException($e->getMessage(), $e->getCode(), $e);
        $ne->errorInfo = $e->errorInfo;
        throw $ne;
      }
      throw $e;
    }
    $sth->setFetchMode(PDO::FETCH_OBJ);
    return $sth;
  }

  public static function query1($sql, $args) {
    $sth = static::query($sql, $args);
    $obj = $sth->fetch();
    $sth->closeCursor();
    return $obj;
  }
}

?>
