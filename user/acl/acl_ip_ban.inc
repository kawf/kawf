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
           "ON (ib.ban_type_id = bt.id) WHERE ib.ip_id = ? ORDER BY bt.id";
    $sth = db_query($sql, array($ip_id));
    $bans = array();
    while($row = $sth->fetch()) {
      $bans[] = $row[0];
    }
    $sth->closeCursor();
    return $bans;
  }

  protected static function find_bans_by_ban_ids($ban_ids) {
    if(!$ban_ids) {
      return array();
    }
    $ids = array();
    $params = array();
    foreach($ban_ids as $ban_id) {
      $ids[] = (int)$ban_id;
      $params[] = '?';
    }
    $sth = db_query("SELECT ban_type FROM acl_ban_types WHERE id IN (" .
                    implode(",", $params) . ") ORDER BY id",
                    $ids);
    $bans = array();
    while($row = $sth->fetch()) {
      $bans[] = $row[0];
    }
    $sth->closeCursor();
    return $bans;
  }

  protected static function find_ban_type_ids($banstring_list) {
    if(!$banstring_list) {
      return array();
    }
    $params = array();
    foreach($banstring_list as $banstring) {
      $params[] = '?';
    }
    $sth = db_query("SELECT id FROM acl_ban_types " .
                     "WHERE ban_type IN (" . implode(",", $params) . ")",
                     $banstring_list);
    $ids = array();
    while($row = $sth->fetch()) {
      $ids[] = (int)$row[0];
    }
    $sth->closeCursor();
    return $ids;
  }

  public static function find($ip_id) {
    $ip_id = (int)$ip_id;
    $row = db_query_first("SELECT id, INET_NTOA(ip) AS ipstring, " .
                          "       INET_NTOA(mask) AS maskstring, note " .
                          "FROM acl_ips " .
                          "WHERE id = ?",
                          array($ip_id));
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
    $sth = db_query("SELECT id, INET_NTOA(ip) AS ipstring, " .
                    "       INET_NTOA(mask) AS maskstring, note " .
                    "FROM acl_ips");
    $acl_ip_bans = array();
    while($row = $sth->fetch()) {
      list($id, $ipstring, $maskstring, $note) = $row;
      $id = (int)$id;
      $bans = self::find_bans_by_ip_id($id);
      $acl_ip_bans[] = new self($id, $ipstring, $maskstring, $note, $bans);
    }
    $sth->closeCursor();
    return $acl_ip_bans;
  }

  public static function find_matching_bans($otherip) {
    // Return a list of all AclIpBan objects that match $otherip:
    // $bans = AclIpBan::find_matching_bans("1.2.3.4");  // return an array
    $sth = db_query("SELECT id, INET_NTOA(ip) AS ipstring, " .
                    "       INET_NTOA(mask) AS maskstring, note " .
                    "FROM acl_ips " .
                    "WHERE ip & mask = INET_ATON(?) & mask",
                    array($otherip));
    $acl_ip_bans = array();
    while($row = $sth->fetch()) {
      list($id, $ipstring, $maskstring, $note) = $row;
      $id = (int)$id;
      $bans = self::find_bans_by_ip_id($id);
      $acl_ip_bans[] = new self($id, $ipstring, $maskstring, $note, $bans);
    }
    $sth->closeCursor();
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
      db_exec("INSERT INTO acl_ips (ip, mask, note) " .
              "VALUES (INET_ATON(?), INET_ATON(?), ?)",
              array($this->ipstring, $this->maskstring, $this->note));
      $this->id = db_last_insert_id();

      $ban_type_ids = self::find_ban_type_ids($this->bans);
      $this->bans = self::find_bans_by_ban_ids($ban_type_ids);
      if($ban_type_ids) {
        $values = array();
        $insert_sql_args = array();
        foreach($ban_type_ids as $ban_type_id) {
          $values[] = "(?, ?)";
          $insert_sql_args[] = $this->id;
          $insert_sql_args[] = $ban_type_id;
        }
        $insert_sql = "INSERT INTO acl_ip_bans (ip_id, ban_type_id) " .
                      "VALUES " . implode(",", $values);
      }
      // MyISAM does not support transactions, so lock the table during
      // this change to avoid a race condition.
      db_exec("LOCK TABLES acl_ip_bans WRITE");
      db_exec("DELETE FROM acl_ip_bans WHERE ip_id = ?", array($this->id));
      if($ban_type_ids) {
        db_exec($insert_sql, $insert_sql_args);
      }
      db_exec("UNLOCK TABLES");
      $this->changed_attributes = array();

    } else {
      // Update existing records.
      if(!$this->changed_attributes) {
        // Nothing changed, nothing to do.
        return;
      }
      $updates = array();
      $sql_args = array();
      if(array_key_exists("ipstring", $this->changed_attributes)) {
        if(!self::is_ip_valid($this->ipstring)) {
          throw new AclIpBanInvalidIp($this->ipstring);
        }
        $updates[] = "ip = INET_ATON(?)";
        $sql_args[] = $this->ipstring;
      }
      if(array_key_exists("maskstring", $this->changed_attributes)) {
        if(!self::is_ip_valid($this->maskstring)) {
          throw new AclIpBanInvalidMask($this->maskstring);
        }
        $updates[] = "mask = INET_ATON(?)";
        $sql_args[] = $this->maskstring;
      }
      if(array_key_exists("note", $this->changed_attributes)) {
        $updates[] = "note = ?";
        $sql_args[] = $this->note;
      }
      if($updates) {
        $sql = "UPDATE acl_ips SET " . implode(", ", $updates) .
               " WHERE id = ?";
        $sql_args[] = $this->id;
        db_exec($sql, $sql_args);
      }

      if(array_key_exists("bans", $this->changed_attributes)) {
        $ban_type_ids = self::find_ban_type_ids($this->bans);
        $this->bans = self::find_bans_by_ban_ids($ban_type_ids);
        if($ban_type_ids) {
          $values = array();
          $insert_sql_args = array();
          foreach($ban_type_ids as $ban_type_id) {
            $values[] = "(?, ?)";
            $insert_sql_args[] = $this->id;
            $insert_sql_args[] = $ban_type_id;
          }
          $insert_sql = "INSERT INTO acl_ip_bans (ip_id, ban_type_id) " .
                        "VALUES " . implode(", ", $values);
        }
        // MyISAM does not support transactions, so lock the table during
        // this change to avoid a race condition.
        db_exec("LOCK TABLES acl_ip_bans WRITE");
        db_exec("DELETE FROM acl_ip_bans WHERE ip_id = ?", array($this->id));
        if($ban_type_ids) {
          db_exec($insert_sql, $insert_sql_args);
        }
        db_exec("UNLOCK TABLES");
      }
      // Save succeeded, this object is clean now.
      $this->changed_attributes = array();
    }
  }

  public function delete() {
    // Delete this record from the database.
    if(!is_null($this->id)) {
      db_exec("DELETE FROM acl_ip_bans WHERE ip_id = ?", array($this->id));
      db_exec("DELETE FROM acl_ips WHERE id = ?", array($this->id));
    }
    $this->id = null;
    $this->ipstring = "";
    $this->maskstring = "";
    $this->note = null;
    $this->bans = array();
  }
}

?>
