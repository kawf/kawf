<?php
# f_messages* and f_threads* are create automatically when you create a form
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
    index (pid),
    index (tid)
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

# Unused
$create_visits_table = "
  create table f_visits (
    ip varchar(20) not null,
    aid int not null,
    tstamp timestamp,
    key (ip),
    key (aid)
  )";

# Create all of these by hand
$create_forums_table = "
  create table f_forums (
    fid int not null auto_increment,
    name varchar(150) not null,
    shortname varchar(20) not null,
    options set('Read','PostThread','PostReply','PostEdit','OffTopic','Searchable') not null default 'Read,PostThread,PostReply,PostEdit,Searchable',
    primary key (fid)
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
    ip varchar(20) not null,
    tstamp datetime not null,
    primary key (cookie),
    key (aid),
    key (ip)
  )";

$create_unique_table = "
  create table f_unique (
    fid int not null,
    type enum('Message','Thread') not null,
    id int not null,
    primary key (fid, type, id)
  )";

$create_tracking_table = "
  create table f_tracking (
    fid int not null,
    tid int not null,
    aid int not null,
    tstamp timestamp,
    options set('SendEmail') not null,
    key (fid, aid)
  )";

$create_update_table = "
  create table f_updates (
    fid int not null,
    mid int not null,
    primary key (fid)
  )";

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

$create_users_table = "
  create table u_users (
    aid int not null auto_increment,
    name varchar(50) not null,
    shortname varchar(50) not null,
    email varchar(100) not null,
    password varchar(50) not null,
    status enum('Create','Active','Suspended') not null default 'Create',
    createdate datetime not null,
    cookie varchar(50) not null,
    timezone varchar(5) not null,
    style enum('Classic','Simple','UBB') not null default 'Classic',
    preferences set('ShowOffTopic','ShowModerated','Collapsed','SecretEmail','FlatThread','SimpleHTML','AutoTrack','HideSignatures','AutoUpdateTracking','OldestFirst','SortbyActive','CollapseOffTopic') not null,
    signature text not null,
    threadsperpage int not null,
    posts int not null,
    primary key (aid),
    unique (shortname),
    unique (email),
    unique (cookie)
  )";

$create_global_messages_table = "
  create table f_global_messages (
    gid int not null,
    subject text not null,
    url varchar(200) not null,
    name varchar(50) not null,
    date datetime not null default '0000-00-00 00:00:00',
    primary key (gid)
  )";

$create_offtopic_table = "
  create table f_offtopic (
    fid int not null,
    mid int not null,
    aid int not null,
    tstamp timestamp,
    key (fid)
  )";

$create_pending_table = "
  create table u_pending (
    tid int not null,
    aid int not null,
    cookie varchar(15) not null,
    type enum('NewAccount','ChangeEmail','ForgotPassword') not null,
    data blob,
    tstamp timestamp,
    status enum('Sent','Bounce','Done') not null,
    primary key (tid),
    key (aid),
    unique key (cookie)
  )";

?>
