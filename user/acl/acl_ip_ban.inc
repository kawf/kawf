<?php

class AclIpBanException extends Exception {}
class AclIpBanInvalidBans extends AclIpBanException {}
class AclIpBanInvalidIp extends AclIpBanException {}
class AclIpBanInvalidMask extends AclIpBanException {}

class AclIpBan {
  // AclIpBan model, stores on IP address/mask, and a list of bans
  // associated with it.
  protected $id, $ipstring, $maskstring, $note, $bans;
  protected $changed_attributes;

  protected static function find_bans_by_ip_id($ip_id) {
    $ip_id = (int)$ip_id;
    $sql = "SELECT bt.ban_type " .
           "FROM acl_ip_bans ib INNER JOIN acl_ban_types bt " .
           "ON (ib.ban_type_id = bt.id) WHERE ib.ip_id = $ip_id ORDER BY bt.id";
    $result = sql_query($sql);
    $bans = array();
    while($row = sql_fetch_array($result)) {
      $bans[] = $row[0];
    }
    sql_free_result($result);
    return $bans;
  }

  protected static function find_bans_by_ban_ids($ban_ids) {
    if(!$ban_ids) {
      return array();
    }
    $ids = array();
    foreach($ban_ids as $ban_id) {
      $ids[] = (int)$ban_id;
    }
    $result = sql_query("SELECT ban_type FROM acl_ban_types WHERE id IN (" .
                        implode(", ", $ids) . ") ORDER BY id");
    $bans = array();
    while($row = sql_fetch_array($result)) {
      $bans[] = $row[0];
    }
    sql_free_result($result);
    return $bans;
  }

  protected static function find_ban_type_ids($banstring_list) {
    if(!$banstring_list) {
      return array();
    }
    $qbans = array();
    foreach($banstring_list as $banstring) {
      $qbans[] = sql_escape($banstring);
    }
    $result = sql_query("SELECT id FROM acl_ban_types " .
                        "WHERE ban_type IN (" . implode(", ", $qbans) . ")");
    $ids = array();
    while($row = sql_fetch_array($result)) {
      $ids[] = (int)$row[0];
    }
    sql_free_result($result);
    return $ids;
  }

  public static function find($ip_id) {
    $ip_id = (int)$ip_id;
    $row = sql_querya("SELECT id, INET_NTOA(ip) AS ipstring, " .
                      "       INET_NTOA(mask) AS maskstring, note " .
                      "FROM acl_ips " .
                      "WHERE id = $ip_id");
    if($row) {
      list($id, $ipstring, $maskstring, $note) = $row;
      $id = (int)$id;
      $bans = self::find_bans_by_ip_id($id);
      return new self($id, $ipstring, $maskstring, $note, $bans);
    } else {
      return null;
    }
  }

  public static function find_all() {
    $result = sql_query("SELECT id, INET_NTOA(ip) AS ipstring, " .
                        "       INET_NTOA(mask) AS maskstring, note " .
                        "FROM acl_ips");
    $acl_ip_bans = array();
    while($row = sql_fetch_array($result)) {
      list($id, $ipstring, $maskstring, $note) = $row;
      $id = (int)$id;
      $bans = self::find_bans_by_ip_id($id);
      $acl_ip_bans[] = new self($id, $ipstring, $maskstring, $note, $bans);
    }
    sql_free_result($result);
    return $acl_ip_bans;
  }

  public static function find_matching_bans($otherip) {
    // Return a list of all AclIpBan objects that match $otherip:
    // $bans = AclIpBan::find_matching_bans("1.2.3.4");  // return an array
    $qotherip = sql_escape($otherip);
    $result = sql_query("SELECT id, INET_NTOA(ip) AS ipstring, " .
                        "       INET_NTOA(mask) AS maskstring, note " .
                        "FROM acl_ips " .
                        "WHERE ip & mask = INET_ATON($qotherip) & mask");
    $acl_ip_bans = array();
    while($row = sql_fetch_array($result)) {
      list($id, $ipstring, $maskstring, $note) = $row;
      $id = (int)$id;
      $bans = self::find_bans_by_ip_id($id);
      $acl_ip_bans[] = new self($id, $ipstring, $maskstring, $note, $bans);
    }
    sql_free_result($result);
    return $acl_ip_bans;
  }

  public static function is_ip_valid($ipstring) {
    $octets = explode(".", $ipstring, 4);
    if(count($octets) != 4) {
      return false;
    }
    foreach($octets as $octet) {
      if(!preg_match('/^\d+$/', $octet) or (int)$octet > 255) {
        return false;
      }
    }
    return true;
  }

  public static function create() {
    // Return a new empty instance.  Use the set_ methods to add data.
    return new self(null, null, null, null, null);
  }

  protected function __construct($id, $ipstring, $maskstring, $note, $bans) {
    // $id - the unique id of this AclIpBan instance or null if it's new
    // $ipstring - "1.2.3.4"
    // $maskstring - "255.255.255.0"
    // $note - a note string, or null
    // $bans - a list of string representations of bans, like
    //         array("account_creation", "login")
    $this->id = $id ? (int)$id : null;
    $this->ipstring = (string)$ipstring;
    $this->maskstring = (string)$maskstring;
    $this->note = $note ? (string)$note : null;

    if(is_array($bans)) {
      $this->bans = $bans;
    } elseif(is_null($bans)) {
      $this->bans = array();
    } else {
      throw new AclIpBanInvalidBans("$bans");
    }

    $this->changed_attributes = array();
  }

  public function __toString() {
    $id = is_null($this->id) ? "NULL" : (int)$this->id;
    $note = $this->note ? "'" . $this->note . "'" : "NULL";
    $bans = array();
    foreach($this->bans as $ban) {
      $bans[] = "'$ban'";
    }
    $bans = implode(", ", $bans);
    return sprintf("<%s(%s) ipstring:'%s' maskstring:'%s' note:%s bans:[%s]>",
                   get_class($this), $id, $this->ipstring,
                   $this->maskstring, $note, $bans);
  }

  public function id() {
    return $this->id;
  }

  public function ipstring() {
    return $this->ipstring;
  }

  public function maskstring() {
    return $this->maskstring;
  }

  public function note() {
    return $this->note;
  }

  public function is_account_creation_banned() {
    return in_array("account_creation", $this->bans);
  }

  public function is_posts_banned() {
    return in_array("posts", $this->bans);
  }

  public function is_login_banned() {
    return in_array("login", $this->bans);
  }

  public function is_all_banned() {
    return in_array("all", $this->bans);
  }

  public function set_ipstring($ipstring) {
    $this->ipstring = (string)$ipstring;
    $this->changed_attributes["ipstring"] = true;
  }

  public function set_maskstring($maskstring) {
    $this->maskstring = (string)$maskstring;
    $this->changed_attributes["maskstring"] = true;
  }

  public function set_note($note) {
    $this->note = $note ? (string)$note : null;
    $this->changed_attributes["note"] = true;
  }

  public function set_bans($bans) {
    if(is_array($bans)) {
      $this->bans = $bans;
    } elseif(is_null($bans)) {
      $this->bans = array();
    } else {
      throw new AclIpBanInvalidBans("$bans");
    }
    $this->changed_attributes["bans"] = true;
  }

  public function save() {
    if(is_null($this->id)) {
      // Create new records.
      if(!self::is_ip_valid($this->ipstring)) {
        throw new AclIpBanInvalidIp($this->ipstring);
      }
      if(!self::is_ip_valid($this->maskstring)) {
        throw new AclIpBanInvalidMask($this->maskstring);
      }
      sql_execute("INSERT INTO acl_ips (ip, mask, note) " .
                  "VALUES (INET_ATON(" . sql_escape($this->ipstring) . "), " .
                  "INET_ATON(" . sql_escape($this->maskstring) . "), " .
                  sql_escape($this->note) . ")");
      $this->id = sql_last_insert_id();

      $ban_type_ids = self::find_ban_type_ids($this->bans);
      $this->bans = self::find_bans_by_ban_ids($ban_type_ids);
      if($ban_type_ids) {
        $values = array();
        foreach($ban_type_ids as $ban_type_id) {
          $values[] = "(" . $this->id . ", $ban_type_id)";
        }
        $insert_sql = "INSERT INTO acl_ip_bans (ip_id, ban_type_id) " .
                      "VALUES " . implode(", ", $values);
      }
      // MyISAM does not support transactions, so lock the table during
      // this change to avoid a race condition.
      sql_execute("LOCK TABLES acl_ip_bans WRITE");
      sql_execute("DELETE FROM acl_ip_bans WHERE ip_id = " . $this->id);
      if($ban_type_ids) {
        sql_execute($insert_sql);
      }
      sql_execute("UNLOCK TABLES");
      $this->changed_attributes = array();

    } else {
      // Update existing records.
      if(!$this->changed_attributes) {
        // Nothing changed, nothing to do.
        return;
      }
      $updates = array();
      if(array_key_exists("ipstring", $this->changed_attributes)) {
        if(!self::is_ip_valid($this->ipstring)) {
          throw new AclIpBanInvalidIp($this->ipstring);
        }
        $updates[] = "ip = INET_ATON(" . sql_escape($this->ipstring) . ")";
      }
      if(array_key_exists("maskstring", $this->changed_attributes)) {
        if(!self::is_ip_valid($this->maskstring)) {
          throw new AclIpBanInvalidMask($this->maskstring);
        }
        $updates[] = "mask = INET_ATON(" . sql_escape($this->maskstring) . ")";
      }
      if(array_key_exists("note", $this->changed_attributes)) {
        $updates[] = "note = " . sql_escape($this->note);
      }
      if($updates) {
        $sql = "UPDATE acl_ips SET " . implode(", ", $updates) .
               " WHERE id = " . $this->id;
        sql_execute($sql);
      }

      if(array_key_exists("bans", $this->changed_attributes)) {
        $ban_type_ids = self::find_ban_type_ids($this->bans);
        $this->bans = self::find_bans_by_ban_ids($ban_type_ids);
        if($ban_type_ids) {
          $values = array();
          foreach($ban_type_ids as $ban_type_id) {
            $values[] = "(" . $this->id . ", $ban_type_id)";
          }
          $insert_sql = "INSERT INTO acl_ip_bans (ip_id, ban_type_id) " .
                        "VALUES " . implode(", ", $values);
        }
        // MyISAM does not support transactions, so lock the table during
        // this change to avoid a race condition.
        sql_execute("LOCK TABLES acl_ip_bans WRITE");
        sql_execute("DELETE FROM acl_ip_bans WHERE ip_id = " . $this->id);
        if($ban_type_ids) {
          sql_execute($insert_sql);
        }
        sql_execute("UNLOCK TABLES");
      }
      // Save succeeded, this object is clean now.
      $this->changed_attributes = array();
    }
  }

  public function delete() {
    // Delete this record from the database.
    if(!is_null($this->id)) {
      sql_execute("DELETE FROM acl_ip_bans WHERE ip_id = " . $this->id);
      sql_execute("DELETE FROM acl_ips WHERE id = " . $this->id);
    }
    $this->id = null;
    $this->ipstring = "";
    $this->maskstring = "";
    $this->note = null;
    $this->bans = array();
  }
}

?>
