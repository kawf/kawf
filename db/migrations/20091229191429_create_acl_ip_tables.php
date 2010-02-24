<?php

class CreateAclIpTables extends DatabaseMigration {
  public function migrate() {
    $sql = "CREATE TABLE acl_ips (" .
           "  id INT NOT NULL AUTO_INCREMENT, " .
           "  ip VARCHAR(18) NOT NULL, " .
           "  note VARCHAR(255), " .
           "  PRIMARY KEY (id), " .
           "  UNIQUE KEY (ip)" .
           ")";
    $this->execute_sql($sql);

    $sql = "CREATE TABLE acl_ban_types (" .
           "  id INT NOT NULL, " .
           "  ban_type VARCHAR(50) NOT NULL, " .
           "  PRIMARY KEY (id), " .
           "  UNIQUE KEY (ban_type)" .
           ")";
    $this->execute_sql($sql);

    $sql = "INSERT INTO acl_ban_types " .
           "  (id, ban_type) " .
           "VALUES " .
           "  (1, 'account_creation'), " .
           "  (2, 'posts'), " .
           "  (3, 'login'), " .
           "  (4, 'all')";
    $this->execute_sql($sql);

    $sql = "CREATE TABLE acl_ip_bans (" .
           "  id INT NOT NULL AUTO_INCREMENT, " .
           "  ip_id INT NOT NULL, " .
           "  ban_type_id INT NOT NULL, " .
           "  PRIMARY KEY (id), " .
           "  UNIQUE KEY (ip_id, ban_type_id)" .
           ")";
    $this->execute_sql($sql);
  }
}

?>
