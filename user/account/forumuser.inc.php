<?php

require_once("util.inc.php");	/* for getmicrotime() */
/* Seed the random number generator */
mt_srand(getmicrotime());

require_once("sql.inc.php");
require_once("validate.inc.php");
require_once("mailfrom.inc.php");
require_once("strip.inc.php");
require_once("timezone.inc.php");

/* This is the standard ForumUser class */
#[AllowDynamicProperties] /* temp workaround for Issue #77 */
class ForumUser {
  var $aid, $name, $email, $status, $createdate, $createip;

  var $shortname, $password, $cookie, $gmsgfilter;

  var $timezone, $style, $preferences, $signature, $threadsperpage, $posts, $pref, $tokentime, $capable, $tzoff;

  function __construct($aid = null, $req = true)
  {
    if (isset($aid)) $this->find_by_aid((int)$aid, $req);
    else $this->find_by_cookie();
  }

  /* You shouldn't call this directly */
  function find($where, $req = true)
  {
    global $tz_to_name;

    $this->tzoff = 0;

    $user = db_query_first("select * from u_users where $where");
    if ($user) {
      foreach ($user as $type => $value) {
        $this->$type = $value;
      }

      if (!empty($this->preferences)) {
        $preferences = explode(",", $this->preferences);
        foreach ($preferences as $flag)
          $this->pref[$flag] = true;
      }

      if(strlen($this->timezone) && class_exists('DateTimeZone')) {
	/* convert 5 chars into full tz */
        if (strlen($this->timezone)<6)
	    $this->timezone = $tz_to_name[$this->timezone];

	$zoneUser= new DateTimeZone($this->timezone);
	$zoneLocal= new DateTimeZone(date_default_timezone_get());

	$timeLocal = new DateTime("now", $zoneLocal);

	/* tzoff is offset between PHP server local time and user viewer local
           time */
	$this->tzoff =  $zoneLocal->getOffset($timeLocal)-
			$zoneUser->getOffset($timeLocal);
      } else {
	$this->timezone = date_default_timezone_get();
      }

      /* Set token time rounded down to 15 minutes UTC. */
      $this->tokentime = 900 * (int)(time() / 900);

    } else if ($req) {
	global $login_to_read;
	if(isset($login_to_read) && $login_to_read) $this->req();
    }

    return $this;
  }

  /* These functions are used to find a user - NOTE! always try to call find,
     even for invalid/non-existent users or tzoff wont ever get set! */
  function find_by_aid($aid, $req = true)
  {
    if (!is_int($aid)) return $this->find('0', $req);

    return $this->find("aid = $aid", $req);
  }

  function find_by_email($email)
  {
    return $this->find("email = '" . addslashes($email) . "'");
  }

  function find_by_cookie()
  {
    global $_COOKIE;

    if (!isset($_COOKIE['KawfAccount']) || $_COOKIE['KawfAccount']=='') return $this->find('0');

    return $this->find("cookie = '" . addslashes($_COOKIE['KawfAccount']) . "'");
  }

  /* Returns true if this variable is a valid user */
  function valid()
  {
    if (isset($this->aid))
      return true;
    else
      return false;
  }

  function req()
  {
    global $account_host;
    global $script_name, $path_info, $server_name, $server_port, $http_host;

    if ($this->valid())
      return true;

    /* prevent recursion */
    if ($script_name . $path_info == '/login.phtml')
      return true;

    /* always allow people to create */
    if ($script_name . $path_info == '/create.phtml')
      return true;

    if ($script_name . $path_info == '/finish.phtml')
      return true;

    /* always allow people to request password */
    if ($script_name . $path_info == '/forgotpassword.phtml')
      return true;

    $url = url_origin($_SERVER) . $script_name . $path_info;
    header("Location: /login.phtml?url=$url");
    exit;
  }

  function signature($signature)
  {
    global $standard_tags;

    $signature = trim($signature);
    $signature = striptag($signature, $standard_tags);

    if ($signature != $this->signature)
      $this->update_f['signature'] = $signature;

    $this->signature = $signature;

    return true;
  }

  function threadsperpage($threadsperpage)
  {
    if ($threadsperpage != $this->threadsperpage)
      $this->update_f['threadsperpage'] = $threadsperpage;

    $this->threadsperpage = $threadsperpage;

    return true;
  }

  function preference($name, $value)
  {
    if (isset($this->pref[$name]) != $value) {
      if ($value)
        $this->pref[$name] = true;
      else
        unset($this->pref[$name]);

      if (isset($this->pref))
        foreach ($this->pref as $var => $value)
          $prefs[] = $var;

      if (isset($prefs))
        $this->update_f['preferences'] = implode(",", $prefs);
      else
        $this->update_f['preferences'] = "";
    }
  }

  function set_timezone($timezone)
  {
    if ($timezone != $this->timezone)
      $this->update_f['timezone'] = $timezone;

    $this->timezone = $timezone;

    return true;
  }

  function update()
  {
    if (!$this->aid)
      return false;

    if (empty($this->update_f))
      return true;

    /* Create a new array */
    $update = array();
    $args = array();
    foreach ($this->update_f as $key => $value) {
      $update[] = "$key = ?";
      $args[] = $value;
    }

    $set = implode(", ", $update);
    $args[] = $this->aid;

    db_exec("update u_users set $set where aid = ?", $args);

    return true;
  }

  function post($fid, $status, $count)
  {
    $num = db_exec("update f_upostcount set count = count + ? where aid = ? and fid = ? and status = ?", array($count, $this->aid, $fid, $status));
    if (!$num) {
      db_exec("insert into f_upostcount ( aid, fid, status, count ) values ( ?, ?, ?, 0 )", array($this->aid, $fid, $status));
      db_exec("update f_upostcount set count = count + ? where aid = ? and fid = ? and status = ?", array($count, $this->aid, $fid, $status));
    }
  }

  function moderator($fid)
  {
    if (!$this->valid())
      return false;

    if (isset($this->moderator[$fid]))
      return $this->moderator[$fid];

    $row = db_query_first("select aid from f_moderators where aid = ? and ( fid = ? or fid = -1 )", array($this->aid, $fid));
    $ret = $row ? $row[0] : NULL;
    $this->moderator[$fid] = $ret;

    return $ret;
  }

  function capable($fid, $type)
  {
    if (!$this->valid())
      return false;

    if ($this->admin())
      return true;

    if (isset($this->capable[$fid]))
      return isset($this->capable[$fid][$type]);

    $row = db_query_first("select capabilities from f_moderators where aid = ? and ( fid = ? or fid = -1 )", array($this->aid, $fid));
    $capabilities = $row ? $row[0] : "";

    $caps = explode(",", $capabilities);
    foreach ($caps as $flag)
      $this->capable[$fid][$flag] = true;

    return isset($this->capable[$fid][$type]);
  }

  function token($salt = 'token', $tokentime = NULL)
  {
    $time = $tokentime ? $tokentime : $this->tokentime;
    //$timestr = gmstrftime("%Y%m%d%H%M", $time); // FIXME Deprecated
    $timestr = gmdate("YmdHi", $time);
    return md5($salt . $this->email . $this->password . $timestr);
  }

  function is_valid_token($other_token)
  {
    global $user_token_expiration;
    /* A token is valid if it's the current token, or $user_token_expiration
     * previous ones */
    if ($this->token() == $other_token)
      return true;
    for ($i = 1; $i <= $user_token_expiration; $i++)
      if ($this->token("token", $this->tokentime - 900 * $i) == $other_token)
        return true;

    return false;
  }

  function admin()
  {
    if ($this->aid == 1) return true;
    return false;
  }
}

/* This is the class to change the user stuff */
class AccountUser extends ForumUser {
  function add_pending($type, $data = "")
  {
    do {
      /* We want a 6 digit number */
      $tid = mt_rand(100000, 999999);
      $cookie = substr(md5("pending" . $this->aid . mt_rand()), 0, 15);

      $sql = "insert into u_pending " .
                "( tid, cookie, aid, type, data, status, tstamp ) values " .
                "( ?, ?, ?, ?, ?, 'Sent', NOW() )";
      try {
        db_exec($sql, array($tid, $cookie, $this->aid, $type, $data));
      } catch(PDODuplicateKey $e) {
        continue;
      }
      break;
    } while (TRUE);

    return db_query_first("select * from u_pending where tid = ?", array($tid));
  }

  function send_email($pending)
  {
    global $tpl, $bounce_host, $remote_addr;

    switch($pending['type']) {
    case 'NewAccount':
      $tpl->set_file("mail", "mail/create.tpl");
      $fromprefix = "create";
      $email = $this->email;
      break;
    case 'ForgotPassword':
      $tpl->set_file("mail", "mail/forgotpassword.tpl");
      $fromprefix = "forgotpassword";
      $email = $this->email;
      break;
    case 'ChangeEmail':
      $tpl->set_file("mail", "mail/changeemail.tpl");
      $fromprefix = "changeemail";
      $email = $pending['data'];
      break;
    }

    $tpl->set_var(array(
      "REMOTE_ADDR" => $remote_addr,
      "COOKIE" => $pending['cookie'],
      "TID" => $pending['tid'],
      "EMAIL" => $email,
      "PHPVERSION" => phpversion(),
    ));

    /* Send an email with the directions */
    $message = $tpl->parse("MAIL", "mail");

    $fromaddr = $fromprefix . "-" . $pending['tid'] . "@" . $bounce_host;
    mailfrom($fromaddr, $email, $message);
  }

  function create()
  {
    global $tpl, $bounce_host, $remote_addr;

    $cookie = md5("cookie" . $this->email . microtime());

    $sql = "insert into u_users (name,shortname,email,password,status,cookie,createdate,createip,signature) " .
           "values ( ?, ?, ?, ?, 'Create', ?, NOW(), ?, '' )";
    try {
      db_exec($sql, array($this->name, $this->shortname, $this->email, $this->password, $cookie, $this->createip));
    } catch(PDODuplicateKey $e) {
      $row = db_query_first("select shortname from u_users where shortname = ?", array($this->shortname));
      if ($row)
        $this->shortname = "";

      $row = db_query_first("select name from u_users where name = ?", array($this->name));
      if ($row)
        $this->name = "";

      $row = db_query_first("select email from u_users where email = ?", array($this->email));
      if ($row)
        $this->email = "";

      return false;
    }

    $this->aid = db_last_insert_id();

    $pending = $this->add_pending("NewAccount");

    $this->send_email($pending);

    return $pending['tid'];
  }

  function setcookie()
  {
    global $cookie_host;

    if(!isset($this->cookie) || $this->cookie=='') {
        $this->cookie = md5("cookie" . $this->email . microtime());
	$sql = "update u_users set cookie = ? where aid = ?";
        try {
          db_exec($sql, array($this->cookie, $this->aid));
        } catch(PDODuplicateKey $e) {
	}
    }
    if(is_array($cookie_host)) {
      foreach ($cookie_host as $host)
	setcookie("KawfAccount", $this->cookie, time() + (60 * 60 * 24 * 365 * 5), "/", $host);
    } else {
	setcookie("KawfAccount", $this->cookie, time() + (60 * 60 * 24 * 365 * 5), "/", $cookie_host);
    }
  }

  function unsetcookie()
  {
    global $cookie_host;

    if(isset($this->cookie) && $this->cookie) {
	if(!$this->is_valid_token($_REQUEST['token']))
	    return false;
    }

    if(isset($_GET['all'])) {
        /* generate a new random cookie but dont store it client side.
	   also, DO NOT store a blank cookie in the db */
        $this->cookie = md5("cookie" . $this->email . microtime());
	$sql = "update u_users set cookie = ? where aid = ?";
        try {
          db_exec($sql, array($this->cookie, $this->aid));
        } catch(PDODuplicateKey $e) {
	}
    }
    if(is_array($cookie_host)) {
      foreach ($cookie_host as $host)
	  setcookie("KawfAccount", "", time() - (60 * 60 * 24 * 365), "/", $host);
    } else {
	  setcookie("KawfAccount", "", time() - (60 * 60 * 24 * 365), "/", $cookie_host);
    }

    return true;
  }

  function checkpassword($password)
  {
    global $urlroot;

    if ($this->status != "Active")
      return false;

    if(strlen($this->password) < 60) {
      // Old password, uses MD5.
      if($this->password == md5($password))
        return true;
    } else {
      // New password, uses BCrypt.
      if($this->password == crypt($password, $this->password))
        return true;
    }

    return false;
  }

  function forgotpassword()
  {
    $pending = $this->add_pending("ForgotPassword");

    $this->send_email($pending);

    return $pending['tid'];
  }

  /* Change the users name */
  function name($name)
  {
    //$name = utf8ize($name);

    /* The shortname is a simple way to make sure people don't use names */
    /*  that are too similar */
    /* FIXME: We should handle letters with accents, etc as well - */
    /*        setting the character set to utf8 will probably work. */
    $shortname = strtolower(preg_replace('/[_\W]+/', "", $name));

    if ($name != $this->name)
      $this->update['name'] = $name;
    if ($shortname != $this->shortname)
      $this->update['shortname'] = $shortname;

    $this->name = $name;
    $this->shortname = $shortname;

    return true;
  }

  function createip($ip)
  {
    $this->createip = $ip;
  }

  function verify_email($email)
  {
    $row = db_query_first("select email from u_users where email = ?", array($email));
    if ($row)
      return false;

    $pending = $this->add_pending("ChangeEmail", $email);

    $this->send_email($pending);

    return $pending['tid'];
  }

  /* Change the users email */
  function email($email)
  {
    if (!is_valid_email($email))
      return false;

    if ($email != $this->email)
      $this->update['email'] = $email;

    $this->email = $email;

    return true;
  }

  /* Change the status of the user */
  function password($password)
  {
    // Generate a random 22 character salt.
    $salt_alphabet = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $salt_alphabet_length = strlen($salt_alphabet);
    $salt = '';
    for($i = 0; $i < 22; $i++) {
      $salt .= $salt_alphabet[mt_rand(0, $salt_alphabet_length - 1)];
    }
    // BCrypt the password with salt.
    $pwhash = crypt($password, '$2a$10$' . $salt);

    if ($this->password != $pwhash)
      $this->update['password'] = $pwhash;

    $this->password = $pwhash;

    return true;
  }

  /* Change the status of the user */
  function status($status)
  {
    switch($status) {
    case "Active":
    case "Suspended":
      break;
    default:
      return false;
    }

    if ($status != $this->status)
      $this->update['status'] = $status;

    $this->status = $status;

    return true;
  }

  function update()
  {
    /* Unique columns */
    static $unique = array("name", "shortname", "email");

    if (!$this->valid())
      return false;

    /* Nothing to update, nothing to do */
    if (empty($this->update))
      return true;

    /* Create a new array */
    $update = array();
    $args = array();
    foreach ($this->update as $key => $value) {
      $update[] = "$key = ?";
      $args[] = $value;
    }

    $set = implode(", ", $update);
    $args[] = $this->aid;

    $sql = "update u_users set $set where aid = ?";
    try {
      db_exec($sql, $args);
    } catch(PDODuplicateKey $e) {
      /* Find which column collided (perhaps multiple) */
      foreach ($unique as $value) {
        if (isset($this->update[$value])) {
          $row = db_query_first("select $value from u_users where $value = ?", array($this->update[$value]));
          if ($row)
            $this->$value = null;
        }
      }

      return false;
    }

    return true;
  }
}

?>
