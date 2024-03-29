<?php

# f_messages*, f_threads*, f_sticky*, and associated trigger are create automatically when you create a form
#    pmid int not null,
$create_message_table = "
  create table if not exists f_messages%d (
    mid int not null auto_increment,
    pid int not null,
    tid int not null,
    aid int not null,
    state enum('Active','Moderated','OffTopic','Deleted') not null default 'Active',
    flags set('NewStyle','NoText','Link','Picture','Video','StateLocked') not null,
    name varchar(50) not null,
    email varchar(50) not null,
    date datetime not null default CURRENT_TIMESTAMP,
    ip varchar(47) not null default '0.0.0.0',
    subject text not null,
    message text not null,
    url varchar(250) not null,
    urltext varchar(250) not null,
    video varchar(250) not null,
    views int not null,
    changes text not null,
    primary key (mid),
    fulltext (subject,message),
    index (pid),
    index (tid),
    index (state),
    index (date)
  )";

$create_thread_table = "
  create table if not exists f_threads%d (
    tid int not null auto_increment,
    mid int not null default 0,
    replies int not null default 0,
    flags set('Locked','Sticky') not null,
    tstamp datetime not null default CURRENT_TIMESTAMP,
    primary key (tid),
    index (mid)
  )";

$create_sticky_trigger = "
  create trigger trigger_f%d_sticky_update
  after update on f_threads%d
  for each row
  begin
    if new.flags like '%%STICKY%%' then
      if new.flags <> old.flags then
        insert into f_sticky%d(tid) values (new.tid)
        on duplicate key update tid=tid;
      end if;
    else
      delete from f_sticky%d where tid = new.tid;
  end if;
  end
  ";

$create_sticky_table = "
  create table if not exists f_sticky%d (
    sid int NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT 'Primary Key',
    tid int not null,
    unique (tid)
  )";

# Unused
$create_visits_table = "
  create table if not exists f_visits (
    ip varchar(47) not null,
    aid int not null,
    tstamp timestamp,
    primary key (ip, aid)
  )";

# Create all of these by hand
$create_forums_table = "
  create table if not exists f_forums (
    fid int not null auto_increment,
    name varchar(150) not null,
    shortname varchar(20) not null,
    options set('Read','PostThread','PostReply','PostEdit','OffTopic','Searchable','LoginToRead','ExternallySearchable') not null default 'Read,PostThread,PostReply,PostEdit,Searchable',
    primary key (fid)
  )";

$create_index_table = "
  create table if not exists f_indexes (
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
  create table if not exists f_dupposts (
    fid int not null,
    cookie varchar(40) not null,
    mid int not null,
    aid int not null,
    ip varchar(47) not null,
    tstamp datetime not null default CURRENT_TIMESTAMP,
    primary key (cookie),
    key (aid),
    key (ip)
  )";

$create_unique_table = "
  create table if not exists f_unique (
    fid int not null,
    type enum('Message','Thread') not null,
    id int not null,
    primary key (fid, type, id)
  )";

$create_tracking_table = "
  create table if not exists f_tracking (
    fid int not null,
    tid int not null,
    aid int not null,
    tstamp timestamp,
    options set('SendEmail') not null,
    unique key tid (fid, tid, aid),
    key fid (fid, aid)
  )";

$create_update_table = "
  create table if not exists f_updates (
    fid int not null,
    mid int not null,
    primary key (fid)
  )";

$create_moderators_table = "
  create table if not exists f_moderators (
    aid int not null,
    fid int not null,
    capabilities set('Lock','Moderate','Delete','OffTopic','Advertise') not null,
    unique key (aid, fid)
  )";

$create_upostcount_table = "
  create table if not exists f_upostcount (
    aid int not null,
    fid int not null,
    status enum ('Active','Moderated','Deleted','OffTopic') not null,
    count int not null,
    primary key (aid, fid, status)
  )";

$create_users_table = "
  create table if not exists u_users (
    aid int not null auto_increment,
    name varchar(50) not null,
    shortname varchar(50) not null,
    email varchar(100) not null,
    password varchar(100) not null,
    status enum('Create','Active','Suspended') not null default 'Create',
    createdate datetime not null default CURRENT_TIMESTAMP,
    createip varchar(47),
    cookie varchar(50) not null,
    timezone varchar(20) not null default '',
    style enum('Classic','Simple','UBB') not null default 'Classic',
    preferences set('ShowOffTopic','ShowModerated','Collapsed','SecretEmail','FlatThread','SimpleHTML','AutoTrack','HideSignatures','AutoUpdateTracking','OldestFirst','SortbyActive','CollapseOffTopic','RelativeTimestamps') not null default '',
    signature text not null,
    threadsperpage int not null default '0',
    posts int not null default '0',
    gmsgfilter bigint not null default '0',
    primary key (aid),
    unique (shortname),
    unique (email),
    unique (cookie)
  )";

$create_global_messages_table = "
  create table if not exists f_global_messages (
    gid int not null,
    subject text not null,
    url varchar(200) not null,
    name varchar(50) not null,
    date datetime not null default CURRENT_TIMESTAMP,
    state enum('Active','Inactive') not null default 'Inactive',
    primary key (gid)
  )";

$create_offtopic_table = "
  create table if not exists f_offtopic (
    fid int not null,
    mid int not null,
    aid int not null,
    tstamp timestamp,
    key (fid)
  )";

$create_pending_table = "
  create table if not exists u_pending (
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

$create_preferences_table = "
  create table if not exists u_preferences (
    id int not null,
    name varchar(50) not null,
    primary key (id)
  )";

$insert_static_preferences = "
  replace into u_preferences
    (id, name)
  values
    (1, 'ShowOffTopic'),
    (2, 'ShowModerated'),
    (3, 'Collapsed'),
    (4, 'SecretEmail'),
    (5, 'FlatThread'),
    (6, 'SimpleHTML'),
    (7, 'AutoTrack'),
    (8, 'HideSignatures'),
    (9, 'AutoUpdateTracking'),
    (10, 'OldestFirst'),
    (11, 'SortbyActive'),
    (12, 'CollapseOffTopic'),
    (13, 'ThreadsPerPage'),
    (14, 'Style')
  ";

$create_user_preferences_table = "
  create table if not exists u_user_preferences (
    id int not null auto_increment,
    aid int not null,
    preference_id int not null,
    preference_value varchar(255),
    primary key (id),
    unique key (aid, preference_id)
  )";

# This table should only contain one row - the current
# schema version.
$create_schema_version_table = "
  create table if not exists schema_version (
    integrity_keeper ENUM('') NOT NULL PRIMARY KEY,
    version varchar(14) not null
  )";

$set_current_schema_version = "
  insert into schema_version
    (version)
  values
    (?)
  on duplicate key update
    version = version
  ";

# ACL - IP bans.
$create_acl_ips_table = "
  create table if not exists acl_ips (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip INT UNSIGNED NOT NULL,
    mask INT UNSIGNED NOT NULL,
    proxy_type INT,
    note VARCHAR(255),
    update_time TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY ip_mask_idx (ip, mask)
  )";

$create_acl_proxy_types_table = "
  create table if not exists acl_proxy_types (
    id INT NOT NULL,
    proxy_type VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (proxy_type)
  )";

$insert_acl_proxy_types = "
  replace into acl_proxy_types
    (id, proxy_type)
  VALUES
    (1, 'TOR')";

$create_acl_ban_types_table = "
  create table if not exists acl_ban_types (
    id INT NOT NULL,
    ban_type VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (ban_type)
  )";

$insert_static_ban_types = "
  replace into acl_ban_types
    (id, ban_type)
  VALUES
    (1, 'account_creation'),
    (2, 'posts'),
    (3, 'login'),
    (4, 'all')";

$create_acl_ip_bans_table = "
  create table if not exists acl_ip_bans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_id INT NOT NULL,
    ban_type_id INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (ip_id, ban_type_id)
  )";


?>
