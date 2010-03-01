<?php

require_once("util.inc");	/* for getmicrotime() */
/* Seed the random number generator */
mt_srand(getmicrotime());

require_once("sql.inc");
require_once("validate.inc");
require_once("mailfrom.inc");
require_once("strip.inc");
require_once("timezone.inc");

/* This is the standard ForumUser class */
class ForumUser {
  var $aid, $name, $email, $status, $createdate, $createip;

  var $shortname, $password, $cookie, $gmsgfilter;

  /* You shouldn't call this directly */
  function find($where, $req=true)
  {
    global $tz_to_name;

    $this->tzoff = 0;

    $user = sql_querya("select * from u_users where $where");
    if ($user) {
      foreach ($user as $type => $value) {
        $this->$type = $value;
      }

      if (!empty($this->preferences)) {
        $preferences = explode(",", $this->preferences);
        foreach ($preferences as $flag)
          $this->pref[$flag] = true;
      }

      if(strlen($this->timezone) && strlen($this->timezone)<6
	&& class_exists('DateTimeZone')) {
	$this->timezone = $tz_to_name[$this->timezone];

	$zoneUser= new DateTimeZone($this->timezone);
	$zoneLocal= new DateTimeZone(date_default_timezone_get());

	$timeLocal = new DateTime("now", $zoneLocal);

	/* tzoff is offset between PHP server local time and user viewer local
           time */
	$this->tzoff =  $zoneLocal->getOffset($timeLocal)-
			$zoneUser->getOffset($timeLocal);
      } else {
	/* Workaround Debian bug 571762 */
	/* http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=571762 */
	date_default_timezone_set(@date_default_timezone_get());
	$this->timezone = date_default_timezone_get();
      }

      /* Set token time rounded down to 15 minutes UTC. */
      $this->tokentime = 900 * (int)(time() / 900);

    } elseif ($req) {
	global $login_to_read;
	if(isset($login_to_read)) $this->req();
    }

    return $this;
  }

  /* These functions are used to find a user - NOTE! always try to call find,
     even for invalid/non-existent users or tzoff wont ever get set! */
  function find_by_aid($aid, $req=true)
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

    if (isset($http_host) && !empty($http_host))
      $url = $http_host;
    else {
      $url = $server_name;

      if ($server_port != 80)
        $url .= ":" . $server_port;
    }

    $url .= $script_name . $path_info;

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

    if (isset($account_host))
      header("Location: http://$account_host/login.phtml?url=$url");
    else
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
    foreach ($this->update_f as $key => $value) {
      if (is_int($value))
        $update[] = "$key = $value";
      else
        $update[] = "$key = '" . addslashes($value) . "'";
    }

    $set = implode(", ", $update);

    sql_query("update u_users set $set where aid = " . $this->aid);

    return true;
  }

  function post($fid, $status, $count)
  {
    sql_query("update f_upostcount set count = count + $count where aid = " . $this->aid . " and fid = " . $fid . " and status = '" . addslashes($status) . "'");
    if (!mysql_affected_rows()) {
      _sql_query("insert into f_upostcount ( aid, fid, status, count ) values ( " . $this->aid . ", " . $fid . ", '" . addslashes($status) . "', 0 )");
      sql_query("update f_upostcount set count = count + $count where aid = " . $this->aid . " and fid = " . $fid . " and status = '" . addslashes($status) . "'");
    }
  }

  function moderator($fid)
  {
    if (!$this->valid())
      return false;

    if (isset($this->moderator[$fid]))
      return $this->moderator[$fid];

    $ret = sql_query1("select aid from f_moderators where aid = " . $this->aid .
 " and ( fid = $fid or fid = -1 )");
    $this->moderator[$fid] = $ret;

    return $ret;
  }

  function capable($fid, $type)
  {
    if (!$this->valid())
      return false;

    if (isset($this->capable[$fid]))
      return isset($this->capable[$fid][$type]);

    $capabilities = sql_query1("select capabilities from f_moderators where aid 
= " . $this->aid . " and ( fid = $fid or fid = -1 )");

    $caps = explode(",", $capabilities);
    foreach ($caps as $flag)
      $this->capable[$fid][$flag] = true;

    return isset($this->capable[$fid][$type]);
  }

  function token($salt = 'token', $tokentime = NULL)
  {
    $time = $tokentime ? $tokentime : $this->tokentime;
    $timestr = gmstrftime("%Y%m%d%H%M", $time);
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
                "( $tid, '$cookie', $this->aid, '" . addslashes($type) . "', " .
                "'" . addslashes($data) . "', 'Sent', NOW() )";
      $ret = _sql_query($sql);
    } while (!$ret && mysql_errno() == 1062);

    if (!$ret)
      sql_error();

    return sql_querya("select * from u_pending where tid = $tid");
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

    $qname = sql_escape($this->name);
    $qshortname = sql_escape($this->shortname);
    $qemail = sql_escape($this->email);
    $qpassword = sql_escape($this->password);
    $qcreateip = sql_escape($this->createip);
    $qcookie = sql_escape($cookie);
    $sql = "insert into u_users ( " .
        " name, shortname, email, password, status, cookie, createdate," .
        " createip" .
        ") values ( " .
        "$qname, $qshortname, $qemail, $qpassword, 'Create', $qcookie, " .
        "NOW(), $qcreateip " .
        ")";
    $result = _sql_query($sql);
    if (!$result) {
      if (mysql_errno() != 1062)
        sql_error($sql);

      $tshortname = sql_query1("select shortname from u_users where shortname = $qshortname");
      if ($tshortname != null)
        $this->shortname = "";

      $tname = sql_query1("select name from u_users where name = $qname");
      if ($tname != null)
        $this->name = "";
      
      $temail = sql_query1("select email from u_users where email = $qemail");
      if ($temail != null)
        $this->email = "";

      return false;
    }

    $this->aid = sql_last_insert_id();

    $pending = $this->add_pending("NewAccount");

    $this->send_email($pending);

    return $pending['tid'];
  }

  function setcookie()
  {
    global $cookie_host;

    if(!isset($this->cookie) || $this->cookie=='') {
        $this->cookie = md5("cookie" . $this->email . microtime());
	$sql = "update u_users set cookie = '".$this->cookie."' where aid = " . $this->aid;
	$result = _sql_query($sql);
	if (!$result) {
	  if (mysql_errno() != 1062)
	    sql_error($sql);
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
	$sql = "update u_users set cookie = '". $this->cookie ."' where aid = " . $this->aid;
	$result = _sql_query($sql);
	if (!$result) {
	  if (mysql_errno() != 1062)
	    sql_error($sql);
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

    if ($this->password == md5($password))
      return true;

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
    $val = sql_query1("select email from u_users where email = '" . addslashes($email) . "'");
    if ($val)
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
    $md5password = md5($password);

    if ($this->password != $md5password)
      $this->update['password'] = $md5password;

    $this->password = $md5password;

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
    foreach ($this->update as $key => $value) {
      if (is_int($value))
        $update[] = "$key = $value";
      else
        $update[] = "$key = '" . addslashes($value) . "'";
    }

    $set = implode(", ", $update);

    $sql = "update u_users set $set where aid = " . $this->aid;
    $result = _sql_query($sql);
    if (!$result) {
      if (mysql_errno() != 1062)
        sql_error($sql);

      /* Find which column collided (perhaps multiple) */
      foreach ($unique as $value) {
        if (isset($this->update[$value])) {
          $val = sql_query1("select $value from u_users where $value = '" . addslashes($this->update[$value]) . "'");
          if ($val)
            $this->$value = null;
        }
      }

      return false;
    }

    return true;
  }
}

?>
