<?php
$create_message_table =
  "create table f_messages%d (\n" .
  "  mid int not null auto_increment,\n" .
  "  pmid int not null,\n" .
  "  tid int not null,\n" .
  "  aid int not null,\n" .
  "  state enum('Active','Moderated','Deleted','OffTopic','UserDeleted') not null default 'Active',\n" .
  "  flags set('NewStyle','NoText','Picture','Link') not null,\n" .
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
  "  key (tid)\n" .
  ")";

$create_thread_table =
  "create table f_threads%d (\n" .
  "  tid int not null auto_increment,\n" .
  "  mid int not null,\n" .
  "  replies int not null,\n" .
  "  flags set('Locked') not null,\n" .
  "  tstamp datetime not null,\n" .
  "  primary key (tid)\n" .
  ")";

$create_forums_table = "
  create table f_forums (
    fid int not null auto_increment,
    name varchar(150) not null,
    shortname varchar(20) not null,
    options set('Read','Post') default ('Read','Post'),
    primary key (fid)
  )";

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

$create_dupposts_table = "
  create table f_dupposts (
    fid int not null,
    cookie varchar(40) not null,
    mid int not null,
    aid int not null,
    tstamp datetime not null,
    primary key (cookie),
    key (aid)
  )";

$create_unique_table =
  "create table f_unique (\n" .
  "  fid int not null,\n" .
  "  type enum('Message','Thread') not null,\n" .
  "  id int not null,\n" .
  "  primary key (fid, type, id)\n" .
  ")";

$create_tracking_table =
  "create table f_tracking (\n" .
  "  fid int not null,\n" .
  "  tid int not null,\n" .
  "  aid int not null,\n" .
  "  tstamp timestamp,\n" .
  "  options set('SendEmail') not null,\n" .
  "  key (fid, aid)\n" .
  ")";

$create_update_table =
  "create table f_updates (\n" .
  "  fid int not null,\n" .
  "  mid int not null,\n" .
  "  primary key (fid)\n" .
  ")";

$create_moderators_table = "
  create table f_moderators (
    aid int not null,
    fid int not null,
    key (aid)
  )";

$create_upostcount_table = "
  create table f_upostcount (
    aid int not null,
    fid int not null,
    status enum ('Active','Moderated','Deleted') not null,
    count int not null,
    primary key (aid, fid, status)
  )";

$create_users_table =
  "create table u_users (\n" .
  "  aid int not null auto_increment,\n" .
  "  name varchar(50) not null,\n" .
  "  shortname varchar(50) not null,\n" .
  "  email varchar(100) not null,\n" .
  "  password varchar(50) not null,\n" .
  "  status enum('Create','Active','Suspended') not null default 'Create',\n" .
  "  createdate datetime not null,\n" .
  "  cookie varchar(50) not null,\n" .
  "  timezone varchar(5) not null,\n" .
  "  style enum('Classic','Simple','UBB') not null default 'Classic',\n" .
  "  preferences set('ShowModerated','Collapsed','SecretEmail','FlatThread','AutoTrack','HideSignatures','AutoUpdateTracking','OldestFirst','SortbyActive') not null,\n" .
  "  signature text not null,\n" .
  "  threadsperpage int not null,\n" .
  "  posts int not null,\n" .
  "  primary key (aid),\n" .
  "  unique (shortname),\n" .
  "  unique (email),\n" .
  "  unique (cookie)\n" .
  ")";
?>
