<?

class CreateSchemaVersion extends DatabaseMigration {
  public function migrate() {
    $sql = "CREATE TABLE schema_version (" .
           "  version VARCHAR(14) NOT NULL" .
           ")";
    $result = mysql_query($sql);
    if(! $result) {
      return false;
    }

    $sql = "INSERT INTO schema_version (version) " .
           "VALUES ('')";
    return mysql_query($sql) ? true : false;
  }
}

?>
