#!/usr/bin/php
<?php
$kawf_base = realpath(dirname(__FILE__) . "/..");
require_once($kawf_base . "/config/config.inc");
require_once($kawf_base . "/config/setup.inc");
require_once($kawf_base . "/include/sql.inc");

if(!ini_get('safe_mode'))
    set_time_limit(0);

sql_open($database);

$forum_host = "forums.$domain";
$forum_ip = gethostbyname($forum_host);

echo "Fetching a list of TOR exit nodes that can reach $forum_host ($forum_ip)\n";
$handle = fopen("https://check.torproject.org/cgi-bin/TorBulkExitList.py?ip=$forum_ip", "r");
if(!$handle) {
  echo "Unable to fetch a list of nodes.\n";
  exit(1);
}

$ip_list = array();
while(!feof($handle)) {
  $line = trim(fgets($handle));
  if(substr($line, 0, 1) == "#") {
    continue;
  }
  $ip_list[] = $line;
}
fclose($handle);
echo "Fetched " . count($ip_list) . " IPs.\n";

// Find the TOR proxy_type id.
$result = sql_execute("SELECT id FROM acl_proxy_types WHERE proxy_type = 'TOR' LIMIT 1");
$row = sql_fetch_array($result);
if(!$row) {
  echo "Unable to find the TOR proxy type id.\n";
  exit(1);
}
$tor_proxy_type_id = $row[0];
sql_free_result($result);

// Find the account_creation ban_type id.
$result = sql_execute("SELECT id FROM acl_ban_types WHERE ban_type = 'account_creation' LIMIT 1");
$row = sql_fetch_array($result);
if(!$row) {
  echo "Unable to find the account_creation ban type id.\n";
  exit(1);
}
$account_ban_type_id = $row[0];
sql_free_result($result);

// Iterate over all the IPs and create/update records as needed.
$num_created = 0;
$num_updated = 0;
foreach($ip_list as $ip) {
  $sql = "SELECT ai.id, ai.proxy_type, aib.id " .
         "FROM acl_ips ai LEFT JOIN acl_ip_bans aib " .
         "  ON (ai.id = aib.ip_id AND aib.ban_type_id = $account_ban_type_id) " .
         "WHERE ai.ip = INET_ATON('$ip') AND ai.mask = INET_ATON('255.255.255.255') " .
         "LIMIT 1";
  $result = sql_execute($sql);
  $row = sql_fetch_array($result);
  sql_free_result($result);

  if($row) {
    // This IP is already in the table.
    $updated = false;
    list($ip_id, $proxy_type, $ban_id) = $row;
    if($proxy_type != $tor_proxy_type_id) {
      sql_execute("UPDATE acl_ips SET proxy_type = $tor_proxy_type_id WHERE id = $ip_id");
      $updated = true;
    }
    if(is_null($ban_id)) {
      sql_execute("INSERT INTO acl_ip_bans (ip_id, ban_type_id) VALUES ($ip_id, $account_ban_type_id)");
      $updated = true;
    }
    if($updated) {
      echo "U";
      $num_updated++;
    } else {
      echo ".";
    }
  } else {
    // Insert this IP.
    sql_execute("INSERT INTO acl_ips (ip, mask, proxy_type) " .
                "VALUES (INET_ATON('$ip'), INET_ATON('255.255.255.255'), $tor_proxy_type_id)");
    $ip_id = sql_last_insert_id();
    sql_execute("INSERT INTO acl_ip_bans (ip_id, ban_type_id) VALUES ($ip_id, $account_ban_type_id)");
    echo "C";
    $num_created++;
  }
}

echo "\n";
echo "Created $num_created new bans, updated $num_updated existing bans.\n";

?>
