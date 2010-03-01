<?php

class AddGmsgfilterToUsers extends DatabaseMigration {
  private function column_exists($table, $column) {
    $columns = mysql_query("show columns from $table");
    while ($c = mysql_fetch_assoc($columns)) {
	if($c['Field']==$column) return true;
    }
    return false;
  }
  public function migrate() {
    if (!$this->column_exists('u_users', 'gmsgfilter')) {
	$sql="alter table u_users add column gmsgfilter bigint not null after posts";
	$this->execute_sql($sql);
    }
  }
}

?>
