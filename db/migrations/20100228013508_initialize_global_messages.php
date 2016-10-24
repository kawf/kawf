<?php

class InitializeGlobalMessages extends DatabaseMigration {
  public function migrate() {
    /* unfortunately, f_global_messages was added before our migration tools,
       so there might be db's around w/o it */
    $sql="create table if not exists f_global_messages (
	gid int not null,
	subject text not null,
	url varchar(200) not null,
	name varchar(50) not null,
	date datetime not null default '0000-00-00 00:00:00',
	state enum('Active','Inactive') not null default 'Inactive',
	primary key (gid))";
    db_exec($sql);

    $sql="alter table f_global_messages
	modify state enum('Active','Inactive') not null default 'Inactive'";
    db_exec($sql);
  }
}

?>
