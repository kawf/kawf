<?php
$create_message_table =
  "create table f_messages%d (\n" .
  "  mid int not null auto_increment,\n" .
  "  pid int not null,\n" .
  "  tid int not null,\n" .
  "  aid int not null,\n" .
  "  state enum('Active','Moderated','Deleted') not null default 'Active',\n" .
  "  flags set('NewStyle','NoText','Picture','Link','Locked'),\n" .
  "  name varchar(50) not null,\n" .
  "  email varchar(50) not null,\n" .
  "  date datetime not null default '0000-00-00 00:00:00',\n" .
  "  ip varchar(15) not null default '0.0.0.0',\n" .
  "  subject text not null,\n" .
  "  message text not null,\n" .
  "  url varchar(250) not null,\n" .
  "  urltext varchar(250) not null,\n" .
  "  views int not null,\n" .
  "  changes text not null,\n" .
  "  primary key (mid),\n" .
  "  key (pid),\n" .
  "  key (tid)\n" .
  ")";

$create_thread_table =
  "create table f_threads%d (\n" .
  "  tid int not null auto_increment,\n" .
  "  mid int not null,\n" .
  "  replies int not null,\n" .
  "  tstamp timestamp not null,\n" .
  "  primary key (tid)\n" .
  ")";

$create_forums_table =
  "create table f_forums (\n" .
  "  fid int not null auto_increment,\n" .
  "  name varchar(150) not null,\n" .
  "  shortname varchar(20) not null,\n" .
  "  primary key (fid)\n" .
  ")";

$create_visits_table =
  "create table f_visits (\n" .
  "  ip varchar(20) not null,\n" .
  "  aid int not null,\n" .
  "  tstamp timestamp,\n" .
  "  key (ip),\n" .
  "  key (aid)\n" .
  ")";

$create_index_table =
  "create table f_indexes (\n" .
  "  iid int not null auto_increment,\n" .
  "  fid int not null,\n" . 
  "  minmid int not null,\n" .
  "  maxmid int not null,\n" .
  "  mintid int not null,\n" .
  "  maxtid int not null,\n" .
  "  active int not null,\n" .
  "  moderated int not null,\n" .
  "  deleted int not null,\n" .
  "  primary key (iid),\n" .
  "  key (fid)\n" .
  ")";

$create_dupposts_table =
  "create table f_dupposts (\n" .
  "  fid int not null,\n" .
  "  cookie varchar(40) not null,\n" .
  "  mid int not null,\n" .
  "  tstamp datetime not null,\n" .
  "  primary key (cookie)\n" .
  ")";

$create_unique_table =
  "create table f_unique (\n" .
  "  fid int not null,\n" .
  "  type enum('Message','Thread') not null,\n" .
  "  id int not null,\n" .
  "  primary key (fid,type,id)\n" .
  ")";

$create_tracking_table =
  "create table f_tracking (\n" .
  "  fid int not null,\n" .
  "  tid int not null,\n" .
  "  aid int not null,\n" .
  "  tstamp timestamp,\n" .
  "  options set('SendEmail') not null,\n" .
  "  key (fid,aid)\n" .
  ")";

$create_update_table =
  "create table f_updates (\n" .
  "  fid int not null,\n" .
  "  mid int not null,\n" .
  "  primary key (fid)\n" .
  ")";
?>
