<?php

class AddFullTextIndices extends DatabaseMigration {
  public function migrate() {
    $sth = db_query("select iid from f_indexes order by iid");
    echo "DO NOT INTERRUPT, this could take quite some time!\n";
    while ($i = $sth->fetch() ) {
	$tbl = "f_messages" . $i['iid'];
	$sql = "alter table $tbl add fulltext (subject,message), add index (state), add index (date)";
	echo "Updating $tbl\n";
	db_exec($sql);
    }
    $sth->closeCursor();
  }
}

?>
