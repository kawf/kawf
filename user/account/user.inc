<?php

require_once("sql.inc");

/* This is the standard User class */
class User {
  var $aid, $name, $email, $status, $createdate;

  var $shortname, $password, $cookie;

  function User($data = null)
  {
    global $KawfAccount;

    if (!isset($data))
      return;

    if (is_bool($data) && $data)
      $where = "cookie = '" . addslashes($KawfAccount) . "'";
    else if (is_int($data))
      $where = "aid = '" . addslashes($data) . "'";
    else
      $where = "email = '" . addslashes($data) . "'";

    $user = sql_querya("select * from u_users where $where");
    if (!$user)
      return null;

    foreach ($user as $type => $value)
      $this->$type = $value;

    if (!empty($user->capabilities)) {
      $capabilities = explode(",", $user->capabilities);
      foreach ($capabilities as $flag)
        $user->cap[$flag] = true;
    }

    if (!empty($user->preferences)) {
      $preferences = explode(",", $user->preferences);
      foreach ($preferences as $flag)
        $user->pref[$flag] = true;
    }
  }

  function setcookie()
  {
    global $cookie_host;

    setcookie("KawfAccount", $this->cookie, time() + (60 * 60 * 24 * 365 * 5), "/", 
$cookie_host);
  }

  function unsetcookie()
  {
    global $cookie_host;

    setcookie("KawfAccount", "", time() - (60 * 60 * 24 * 365), "/", $cookie_host);
  }

  function req()
  {
    global $account_host;
    global $SCRIPT_NAME, $PATH_INFO, $SERVER_NAME, $SERVER_PORT, $HTTP_HOST;

    if ($this->aid)
      return true;

    if (isset($HTTP_HOST) && !empty($HTTP_HOST))
      $url = $HTTP_HOST;
    else {
      $url = $SERVER_NAME;

      if ($SERVER_PORT != 80)
        $url .= ":" . $SERVER_PORT;
    }

    $url .= $SCRIPT_NAME . $PATH_INFO;
    header("Location: http://$account_host/login.phtml?url=$url");
    exit;
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
}

/* This is the class to change the user stuff */
class AccountUser extends User {
  function AccountUser($data = null)
  {
    User::User($data);
  }

  function create()
  {
    global $tpl, $bounce_host, $account_host, $REMOTE_ADDR;

    $cookie = md5("cookie" . $this->email . microtime());

    $sql = "insert into u_users ( " .
        " name, shortname, email, password, status, cookie, createdate " .
        ") values ( " .
        "'" . addslashes($this->name) . "', '" . addslashes($this->shortname) . 
"', " .
        "'" . addslashes($this->email) . "', '$this->password', 'Create', " .
        "'$cookie', NOW() ".
        ")";
    $result = _sql_query($sql);
    if (!$result) {
      if (mysql_errno() != 1062)
        sql_error($sql);

      $tshortname = sql_query1("select shortname from u_users where shortname = 
'" . addslashes($this->shortname) . "'");
      if ($tshortname != null)
        $this->shortname = "";

      $tname = sql_query1("select name from u_users where name = '" . addslashes
($this->name) . "'");
      if ($tname != null)
        $this->name = "";
      
      $temail = sql_query1("select email from u_users where email = '" . addslas
hes($this->email) . "'");
      if ($temail != null)
        $this->email = "";

      return false;
    }

    $this->aid = sql_last_insert_id();

    $tpl->set_var(array(
      "REMOTE_ADDR" => $REMOTE_ADDR,
      "HOST" => $account_host,
      "COOKIE" => $cookie,
      "TID" => $tid,
      "EMAIL" => $this->email,
      "PHPVERSION" => phpversion(),
    ));

    /* Send an email with the directions */
    $message = $tpl->parse("MAIL", "create_mail");

    $fromaddr = "create-" . $this->aid . "@" . $bounce_host;
    mailfrom($fromaddr, $this->email, $message);

    return true;
  }

  function forgotpassword()
  {
    global $tpl, $bounce_host, $account_host, $REMOTE_ADDR;

    /* Create a new password */
    $password = "";
    for ($i = 0; $i < 10; $i++)
      $password .= rand() % 26 + 'a';

    $this->password($password);

    $tpl->set_var(array(
      "REMOTE_ADDR" => $REMOTE_ADDR,
      "HOST" => $account_host,
      "PASSWORD" => $password,
      "TID" => $tid,
      "EMAIL" => $this->email,
      "PHPVERSION" => phpversion(),
    ));

    $message = $tpl->parse("MAIL", "forgotpassword_mail");

    $fromaddr = "forgotpassword-" . $this->aid . "@" . $bounce_host;
    mailfrom($fromaddr, $this->email, $message);

    return $tid;
  }

  /* Change the users name */
  function name($name)
  {
    /* The shortname is a simple way to make sure people don't use names */
    /*  that are too similar */
    /* FIXME: We should handle letters with accents, etc as well */
    $shortname = "";
    for ($i = 0; $i < strlen($name); $i++) {
      if (strchr("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789
", substr($name, $i, 1)))
        $shortname .= substr($name, $i, 1);
    }
    $shortname = strtolower($shortname);

    if ($name != $this->name)
      $this->update['name'] = $name;
    if ($shortname != $this->shortname)
      $this->update['shortname'] = $shortname;

    $this->name = $name;
    $this->shortname = $shortname;

    return true;
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

    if (!$this->aid)
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
          $val = sql_query1("select $value from u_users where $value = '" . adds
lashes($this->update[$value]) . "'");
          if ($val)
            $this->$value = null;
        }
      }

      return false;
    }

    return true;
  }
}

