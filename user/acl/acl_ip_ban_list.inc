<?php

class AclIpBanListException extends Exception {}
class AclIpBanListInvalidList extends Exception {}

class AclIpBanList {
  protected $ipstring, $bans;

  public static function find_matching_ban_list($otherip) {
    $bans = AclIpBan::find_matching_bans($otherip);
    if($bans) {
      return new self($otherip, $bans);
    } else {
      return null;
    }
  }

  public function __construct($ipstring, $bans) {
    // $ipstring - "1.2.3.4"
    // $bans - a list of AclIpBan objects
    $this->ipstring = (string)$ipstring;

    if(is_array($bans)) {
      foreach($bans as $ban) {
        if(! $ban instanceof AclIpBan) {
          throw new AclIpBanListInvalidList("$ban is not an AclIpBan");
        }
      }
      $this->bans = $bans;
    } else {
      throw new AclIpBanListInvalidList("$bans is not an Array");
    }
  }

  public function __toString() {
    $bans = implode(", ", $this->bans);
    return sprintf("<%s ipstring:'%s' bans:[%s]>", get_class($this),
                   $this->ipstring, $bans);
  }

  public function ipstring() {
    return $this->ipstring;
  }

  public function bans() {
    return $this->bans;
  }

  public function is_account_creation_banned() {
    foreach($this->bans as $ban) {
      if($ban->is_account_creation_banned()) {
        return true;
      }
    }
    return false;
  }

  public function is_posts_banned() {
    foreach($this->bans as $ban) {
      if($ban->is_posts_banned()) {
        return true;
      }
    }
    return false;
  }

  public function is_login_banned() {
    foreach($this->bans as $ban) {
      if($ban->is_login_banned()) {
        return true;
      }
    }
    return false;
  }

  public function is_all_banned() {
    foreach($this->bans as $ban) {
      if($ban->is_all_banned()) {
        return true;
      }
    }
    return false;
  }
}

?>
