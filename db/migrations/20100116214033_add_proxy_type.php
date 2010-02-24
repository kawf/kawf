<?php

class AddProxyType extends DatabaseMigration {
  public function migrate() {
    $sql = "CREATE TABLE acl_proxy_types (" .
           "  id INT NOT NULL, " .
           "  proxy_type VARCHAR(50) NOT NULL, " .
           "  PRIMARY KEY (id), " .
           "  UNIQUE KEY (proxy_type)" .
           ")";
    $this->execute_sql($sql);


    $sql = "ALTER TABLE acl_ips " .
           "ADD COLUMN proxy_type INT AFTER mask";
    $this->execute_sql($sql);
  }
}

?>
