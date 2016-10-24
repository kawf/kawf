<?php

class AddGmsgfilterToUsers extends DatabaseMigration {
  private function column_exists($table, $column) {
    $sth = db_query("show columns from $table");
    while ($c = $sth->fetch()) {
	if($c['Field']==$column) return true;
    }
    $sth->closeCursor();
    return false;
  }
  public function migrate() {
    if (!$this->column_exists('u_users', 'gmsgfilter')) {
	$sql="alter table u_users add column gmsgfilter bigint not null after posts";
	db_exec($sql);
    }
  }
}

?>
