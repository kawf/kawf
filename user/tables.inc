<?php
#    pmid int not null,
$create_message_table = "
  create table f_messages%d (
    mid int not null auto_increment,
    pid int not null,
    tid int not null,
    aid int not null,
    state enum('Active','Moderated','OffTopic','Deleted') not null default 'Active',
    flags set('NewStyle','NoText','Picture','Link','StateLocked') not null,
    name varchar(50) not null,
    email varchar(50) not null,
    date datetime not null default '0000-00-00 00:00:00',
    ip varchar(15) not null default '0.0.0.0',
    subject text not null,
    message text not null,
    url varchar(250) not null,
    urltext varchar(250) not null,
    views int not null,
    changes text not null,
    primary key (mid),
    key (tid)
  )";

$create_thread_table = "
  create table f_threads%d (
    tid int not null auto_increment,
    mid int not null,
    replies int not null,
    flags set('Locked') not null,
    tstamp datetime not null,
    primary key (tid)
  )";

$create_forums_table = "
  create table f_forums (
    fid int not null auto_increment,
    name varchar(150) not null,
    shortname varchar(20) not null,
    options set('Read','Post') default ('Read','Post'),
    primary key (fid)
  )";

$create_visits_table = "
  create table f_visits (
    ip varchar(20) not null,
    aid int not null,
    tstamp timestamp,
    key (ip),
    key (aid)
  )";

$create_index_table = "
  create table f_indexes (
    iid int not null auto_increment,
    fid int not null, 
    minmid int not null,
    maxmid int not null,
    mintid int not null,
    maxtid int not null,
    active int not null,
    moderated int not null,
    deleted int not null,
    offtopic int not null,
    primary key (iid),
    key (fid)
  )";

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
    capabilities set('Lock','Moderate','Delete','OffTopic','Advertise') not null,
    key (aid, fid)
  )";

$create_upostcount_table = "
  create table f_upostcount (
    aid int not null,
    fid int not null,
    status enum ('Active','Moderated','Deleted','OffTopic') not null,
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

$create_global_messages_table = "
  create table f_global_messages (
    gid int not null,
    subject text not null,
    url varchar(200) not null,
    name varchar(50) not null,
    date datetime not null default '0000-00-00 00:00:00',
    primary key (gid)
  )";
?>
