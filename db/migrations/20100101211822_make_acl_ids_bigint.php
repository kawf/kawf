<?php

class MakeAclIdsBigint extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE acl_ips " .
           "MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT";
    db_exec($sql);

    $sql = "ALTER TABLE acl_ip_bans " .
           "MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT";
    db_exec($sql);
  }
}

?>
