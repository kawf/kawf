<?

class AlterAclIps extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE acl_ips " .
           "DROP KEY ip, " .
           "MODIFY COLUMN ip INT UNSIGNED NOT NULL, " .
           "ADD COLUMN mask INT UNSIGNED NOT NULL AFTER ip, " .
           "ADD COLUMN update_time TIMESTAMP AFTER note, " .
           "ADD UNIQUE KEY ip_mask_idx (ip, mask)";
    $this->execute_sql($sql);
  }
}

?>
