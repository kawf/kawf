<?

class AddCreateipToUsers extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE u_users " .
           "ADD COLUMN createip VARCHAR(15) " .
           "AFTER createdate";
    $this->execute_sql($sql);
  }
}

?>
