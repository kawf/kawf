<?php

class AddTorProxyType extends DatabaseMigration {
  public function migrate() {
    $sql = "INSERT INTO acl_proxy_types " .
           "  (id, proxy_type) " .
           "VALUES " .
           "  (1, 'TOR')";
    db_exec($sql);
  }
}

?>
