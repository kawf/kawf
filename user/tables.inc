<?php
$create_message_table =
  "create table messages%d (\n" .
  "  mid int not null auto_increment,\n" .
  "  pid int not null,\n" .
  "  tid int not null,\n" .
  "  aid int not null,\n" .
  "  state enum('Active','Moderated','Deleted') not null default 'Active',\n" .
  "  flags set('NoText','Picture','Link','Locked'),\n" .
  "  name varchar(50),\n" .
  "  email varchar(50),\n" .
  "  date datetime not null default '0000-00-00 00:00:00',\n" .
  "  ip varchar(15) not null default '0.0.0.0',\n" .
  "  subject text not null,\n" .
  "  message text not null,\n" .
  "  url varchar(250),\n" .
  "  urltext varchar(250),\n" .
  "  primary key (mid),\n" .
  "  key (pid)\n" .
  ")";

$create_thread_table =
  "create table threads%d (\n" .
  "  tid int not null auto_increment,\n" .
  "  mid int not null,\n" .
  "  replies int not null,\n" .
  "  tstamp timestamp not null,\n" .
  "  primary key (tid)\n" .
  ")";

/*
create table indexes (
  iid int not null auto_increment,
  minmid int not null,
  maxmid int not null,
  mintid int not null,
  maxtid int not null,
  active int not null,
  moderated int not null,
  deleted int not null,
  primary key (iid)
);

create table dupposts (
  cookie varchar(40) not null,
  mid int not null,
  tstamp datetime,
  primary key (cookie)
);

create table umessage (
  mid int not null auto_increment,
  tstamp timestamp,
  primary key (mid)
);

create table uthread (
  tid int not null auto_increment,
  tstamp timestamp,
  primary key (tid)
);

create table tracking (
  tid int not null,
  aid int not null,
  tstamp timestamp,
  key (aid)
);

insert into indexes ( iid ) values ( NULL );

create table updates (
  mid int not null,
  primary key (mid)
);
*/
?>
