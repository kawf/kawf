<?php

$user->req();

if ($user->status != 'Active') {
  if (isset($why_url)) 
    header("Location: $why_url");

  echo "Your account isn't validated\n";
  exit;
}

/* Check the data to make sure they entered stuff */
if (!isset($forum)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  Header("Location: http://$SERVER_NAME$SCRIPT_NAME/");
  exit;
}

require_once("textwrap.inc");
require_once("strip.inc");

$tpl->set_file(array(
  "post" => "post.tpl",
  "message" => "message.tpl",
  "forum_header" => "forum/" . $forum['shortname'] . ".tpl",
  "mail" => "mail/followup.tpl",
));

$tpl->set_block("post", "disabled");
$tpl->set_block("disabled", "nonewthreads");
$tpl->set_block("disabled", "noreplies");
$tpl->set_block("post", "locked");
$tpl->set_block("post", "error");
$tpl->set_block("error", "image");
$tpl->set_block("error", "subject_req");
$tpl->set_block("error", "subject_change");
$tpl->set_block("error", "subject_too_long");
$tpl->set_block("post", "preview");
$tpl->set_block("post", "duplicate");
$tpl->set_block("post", "form");
$tpl->set_block("post", "accept");
$tpl->set_block("accept", "refresh_page");

$tpl->set_block("message", "account_id");
$tpl->set_block("message", "forum_admin");
$tpl->set_block("message", "advertiser");
$tpl->set_block("message", "message_ip");
$tpl->set_block("message", "owner");
$tpl->set_block("owner", "delete");
$tpl->set_block("owner", "undelete");
$tpl->set_block("message", "parent");
$tpl->set_block("message", "changes");

$errors = array(
  "image",
  "subject_req",
  "subject_change",
  "subject_too_long",
);

$tpl->set_var(array(
  "forum_admin" => "",
  "advertiser" => "",
  "owner" => "",
  "parent" => "",
  "changes" => "",
));

$tpl->parse("FORUM_HEADER", "forum_header");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");

$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
require_once("ads.inc");

$ad = ads_view("a4.org,aw_" . $forum['shortname'], "_top");
$tpl->_set_var("AD", $ad);

if (!$user->capable($forum['fid'], 'Delete')) {
  if (!isset($tid)) {
    if (!isset($forum['opt.PostThread'])) {
      $tpl->set_var(array(
        "locked" => "",
        "error" => "",
        "preview" => "",
        "duplicate" => "",
        "form" => "",
        "accept" => "",
        "noreplies" => "",
      ));

      $tpl->pparse("CONTENT", "post");
      exit;
    }
  } else {
    if (!isset($forum['opt.PostReply'])) {
      $tpl->set_var(array(
        "locked" => "",
        "error" => "",
        "preview" => "",
        "duplicate" => "",
        "form" => "",
        "accept" => "",
        "nonewthreads" => "",
      ));

      $tpl->pparse("CONTENT", "post");
      exit;
    }
  }
}

$tpl->set_var("disabled", "");

if (isset($tid) && $tid) {
  $index = find_thread_index($tid);
  $sql = "select * from f_threads" . $indexes[$index]['iid'] . " where tid = '" . addslashes($tid) . "'";
  $result = mysql_query($sql) or sql_error($sql);

  $thread = mysql_fetch_array($result);

  $options = explode(",", $thread['flags']);
  foreach ($options as $name => $value)
    $thread["flag.$value"] = true;

  if (isset($thread['flag.Locked']) && !$user->capable($forum['fid'], 'Lock')) {
    $tpl->set_var(array(
      "error" => "",
      "preview" => "",
      "duplicate" => "",
      "form" => "",
      "accept" => "",
    ));

    $tpl->pparse("CONTENT", "post");
    exit;
  }
}

$tpl->set_var("locked", "");

if (isset($postcookie)) {
  /* Strip any tags from the data */
  // $message = preg_replace("/&/", "&amp;", $message);
  $message = striptag($message, $standard_tags);
  $message = demoronize($message);
  $message = stripspaces($message);

  // $subject = preg_replace("/&/", "&amp;", $subject);
  $subject = striptag($subject, $subject_tags);
  $subject = demoronize($subject);
  $subject = stripspaces($subject);

  /* Sanitize the strings */
  $name = stripcrap($user->name);
  if (isset($ExposeEmail))
    $email = stripcrap($user->email);
  else
    $email = "";

  $url = stripcrapurl($url);
  $url = preg_replace("/ /", "%20", $url);

  if (!empty($url) && !preg_match("/^[a-z]+:\/\//i", $url))
    $url = "http://$url";

  $urltext = stripcrap($urltext);

  /* Modify any tirerack links to include our referrer information so we get more money */
  if (!preg_match("/^http:\/\/www\.tirerack\.com\/a\.jsp/", $url) && preg_match("/^http:\/\/www\.tirerack\.com(\/.*)/", $url, $regs)) {
    if (empty($urltext))
      $urltext = $url;

    $url = "http://www.tirerack.com/a.jsp?a=AR4&url=" . urlencode($regs[1]);
  }

  $imageurl = stripcrapurl($imageurl);
  $imageurl = preg_replace("/ /", "%20", $imageurl);

  if (!empty($imageurl) && !preg_match("/^[a-z]+:\/\//i", $imageurl))
    $imageurl = "http://$imageurl";

  if (!isset($pmid) && isset($pid))
    $pmid = $pid;

  if (isset($pmid)) {
    $index = find_msg_index($pmid);
    if (isset($index)) {
      $sql = "select * from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($pmid) . "'";
      $result = mysql_query($sql) or sql_error($sql);

      if (mysql_num_rows($result))
        $parent = mysql_fetch_array($result);
    }
  }

  if (empty($subject) && strlen($subject) == 0)
    $error["subject_req"] = true;
  elseif (isset($parent) && $subject == "Re: " . $parent['subject'] && empty($message) && strlen($message) == 0 && empty($url))
    $error["subject_change"] = true;
  elseif (strlen($subject) > 100) {
    /* Subject is too long */
    $error["subject_too_long"] = true;
    $subject = substr($subject, 0, 100);
  }

  if (!empty($imageurl) && !isset($imgpreview))
    $preview = 1;

  if ((isset($error) || isset($preview)) && !empty($imageurl)) {
    $imgpreview = 1;
    $error["image"] = true;
  }

  if (isset($ExposeEmail)) {
    /* Lame spamification */
    $_email = preg_replace("/@/", "&#" . ord('@') . ";", $user->email);
    $msg_nameemail = "<a href=\"mailto:" . $_email . "\">" . $user->name . "</a>";
  } else
    $msg_nameemail = $user->name;

  if (!empty($imageurl))
    $msg_message = "<center><img src=\"$imageurl\"></center><p>";
  else
    $msg_message = "";

  $msg_message .= nl2br($message);

  if (!empty($url)) {
    if (!empty($urltext))
      $msg_message .= "<ul><li><a href=\"" . $url . "\" target=\"_top\">" . $urltext . "</a></ul>\n";
     else
      $msg_message .= "<ul><li><a href=\"" . $url . "\" target=\"_top\">" . $url . "</a></ul>\n";
  }

  if (!empty($user->signature))
    $msg_message .= "<p>" . nl2br($user->signature) . "\n";

  if (isset($OffTopic))
    $status = "OffTopic";
  else
    $status = "Active";

  $accepted = !isset($error);
} else {
  $message = $urltext = $imageurl = "";

  /* Guaranteed no picture */
  $tpl->set_var("image", "");

  if (isset($pid)) {
    /* Grab the actual message */
    $index = find_msg_index($pid);
    $sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($pid) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    $pmsg = mysql_fetch_array($result);

    if (!ereg("^[Rr][Ee]:", $pmsg['subject'], $sregs))
      $subject = "Re: " . $pmsg['subject'];
     else
      $subject = $pmsg['subject'];
  } else
    $subject = "";
}

if (!isset($preview))
  $tpl->set_var("preview", "");

$date = strftime("%Y-%m-%d %H:%M:%S", time() - $user->tzoff);

$tpl->set_var(array(
  "MSG_MESSAGE" => $msg_message,
  "MSG_NAMEEMAIL" => $msg_nameemail,
  "MSG_SUBJECT" => $subject,
  "MSG_DATE" => $date,
  "MSG_IP" => $REMOTE_ADDR,
  "MSG_AID" => $user->aid,
));

if (isset($error)) {
  foreach ($errors as $n => $e) {
    if (!isset($error[$e]))
      $tpl->set_var($e, "");
  }
} else
  $tpl->set_var("error", "");
    
if (!$accepted || isset($preview)) {
  $action = "post";

  require_once("post.inc");

  $tpl->set_var(array(
    "accept" => "",
    "duplicate" => "",
  ));
} else {
  $flags[] = "NewStyle";

  if (empty($message) && strlen($message) == 0)
    $flags[] = "NoText";

  if (!empty($url) || preg_match("/<[[:space:]]*a[[:space:]]+href/i", $message))
    $flags[] = "Link";

  if (!empty($imageurl) || preg_match("/<[[:space:]]*img[[:space:]]+src/i", $message))
    $flags[] = "Picture";

  $flagset = implode(",", $flags);

  if (!empty($imageurl))
    $message = "<center><img src=\"$imageurl\"></center><p>" . $message;

  /* Add it into the database */
  /* Check to make sure this isn't a duplicate */
  $sql = "insert into f_dupposts ( cookie, fid, aid, ip, tstamp ) values ('" . addslashes($postcookie) . "', " . $forum['fid'] . ", " . $user->aid . ", '" . addslashes($REMOTE_ADDR) . "', NOW() )";
  $result = mysql_query($sql);

  if (!$result) {
    if (mysql_errno() != 1062)
      sql_error($sql);

    $mid = sql_query1("select mid from f_dupposts where cookie = '" . addslashes($postcookie) . "'");
  } else {
    /* Grab a new mid, this should work reliably */
    do {
      $sql = "select max(id) + 1 from f_unique where fid = " . $forum['fid'] . " and type = 'Message'";
      $result = mysql_query($sql) or sql_error($sql);

      list ($mid) = mysql_fetch_row($result);

      $sql = "insert into f_unique ( fid, type, id ) values ( " . $forum['fid'] . ", 'Message', $mid )";
      $result = mysql_query($sql);
    } while (!$result && mysql_errno() == 1062);

    if (!$result)
      sql_error($sql);

    $newmessage = 1;

    sql_query("update f_dupposts set mid = $mid where cookie = '" . addslashes($postcookie) . "'");
  }

  /* Add the message to the last index */
  $index = end($indexes);

  $mtable = "f_messages" . $index['iid'];
  $ttable = "f_threads" . $index['iid'];

  if (!isset($pid) && isset($pmid))
    $pid = $pmid;

  if (!isset($newmessage)) {
    $omsg = sql_querya("select * from $mtable where mid = '" . addslashes($mid) ."'");
    $sql = "update $mtable set " .
	"name = '" . addslashes($name) . "', " .
	"email = '" . addslashes($email) . "', " .
	"ip = '" . addslashes($REMOTE_ADDR) . "', " .
	"flags = '$flagset', " .
	"subject = '" . addslashes($subject) . "', " .
	"message = '" . addslashes($message) . "', " .
	"url = '" . addslashes($url) . "', " .
	"urltext = '" . addslashes($urltext) . "', " .
	"state = '$status' " .
	"where mid = '" . addslashes($mid) . "'";
  } else
    $sql = "insert into $mtable " .
	"( mid, aid, pid, tid, name, email, date, ip, flags, subject, message, url, urltext, state ) values ( '" . addslashes($mid) . "', '".addslashes($user->aid)."', '".addslashes($pid)."', '".addslashes($tid)."', '".addslashes($name)."', '".addslashes($email)."', NOW(), '" . addslashes($REMOTE_ADDR) . "', '$flagset', '".addslashes($subject)."', '".addslashes($message)."', '".addslashes($url)."', '".addslashes($urltext)."', '$status' );";

  $result = mysql_query($sql) or sql_error($sql);

  if (isset($newmessage)) {
    if (!$pmid) {
      /* Grab a new tid, this should work reliably */
      do {
        $sql = "select max(id) + 1 from f_unique where fid = " . $forum['fid'] . " and type = 'Thread'";
        $result = mysql_query($sql) or sql_error($sql);

        list ($tid) = mysql_fetch_row($result);

        $sql = "insert into f_unique ( fid, type, id ) values ( " . $forum['fid'] . ", 'Thread', $tid )";
        $result = mysql_query($sql);
      } while (!$result && mysql_errno() == 1062);

      if (!$result)
        sql_error($sql);

      $sql = "update $mtable set tid = $tid where mid = $mid";
      mysql_query($sql) or sql_error($sql);

      $sql = "insert into $ttable ( tid, mid, tstamp ) values ( $tid, $mid, NOW() )";
      mysql_query($sql) or sql_error($sql);

      $sql = "update f_indexes set maxtid = $tid where iid = " . $index['iid'] . " and maxtid < $tid";
      mysql_query($sql) or sql_error($sql);
    } else {
      $sql = "update $ttable set replies = replies + 1, tstamp = NOW() where tid = '" . addslashes($tid) . "'";
      mysql_query($sql) or sql_error($sql);
    }

    $sql = "update f_indexes set maxmid = $mid where iid = " . $index['iid'] . " and maxmid < $mid";
    mysql_query($sql) or sql_error($sql);

    if (!$pmid) {
      $sql = "update f_indexes set $status = $status + 1 where iid = " . $index['iid'];
      mysql_query($sql) or sql_error($sql);
    }

    $user->post($forum['fid'], $status, 1);
    $tpl->set_var("duplicate", "");
  } else {
    $user->post($forum['fid'], $omsg['state'], -1);

    if (!$pmid)
      sql_query("update f_indexes set " . $omsg['state'] . " = " . $omsg['state'] . " - 1 where iid = " . $index['iid']);
  }

  $sql = "insert into f_updates ( fid, mid ) values ( " . $forum['fid'] . ", '" . addslashes($mid) . "' )";
  mysql_query($sql);

  if (!empty($TrackThread) && isset($newmessage)) {
    $options = "";

    if (isset($EmailFollowup))
      $options = "SendEmail";

    $sql = "select * from f_tracking where fid = " . $forum['fid'] . " and aid = '" . $user->aid . "' and tid = '" . addslashes($tid) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    if (!mysql_num_rows($result)) {
      $sql = "insert into f_tracking ( fid, tid, aid, options ) values ( " . $forum['fid'] . ", '" . addslashes($tid) . "', '" . addslashes($user->aid) . "', '$options' )";
      mysql_query($sql) or sql_error($sql);
    }
  }

  require_once("mailfrom.inc");

  $sql = "select * from f_tracking where fid = " . $forum['fid'] . " and tid = '" . addslashes($tid) . "' and options = 'SendEmail' and aid != " . $user->aid;
  $result = mysql_query($sql) or sql_error($sql);

  if (mysql_num_rows($result) > 0) {
    # This is needed since $index may be trashed --jerdfelt
    $index = find_msg_index($thread['mid']);
    $sql = "select subject from f_messages" . $indexes[$index]['iid'] . " where mid = " . $thread['mid'];
    $res2 = mysql_query($sql) or sql_error($sql);

    list($t_subject) = mysql_fetch_row($res2);

    $e_message = substr($message, 0, 1024);
    if (strlen($message) > 1024) {
      $bytes = strlen($message) - 1024;
      $plural = ($bytes == 1) ? '' : 's';
      $e_message .= "...\n\nMessage continues for another $bytes byte$plural\n";
    }

    $tpl->set_var(array(
      "THREAD_SUBJECT" => $t_subject,
      "USER_NAME" => $user->name,
      "HOST" => $_url,
      "FORUM_SHORTNAME" => $forum['shortname'],
      "MSG_MID" => $mid,
      "MAIL_MSG_SUBJECT" => $subject,
      "MAIL_MSG_MESSAGE" => $e_message,
      "PHPVERSION" => phpversion(),
    ));

    while ($track = mysql_fetch_array($result)) {
      $uuser = new ForumUser;
      $uuser->find_by_aid((int)$track['aid']);

      $tpl->set_var("EMAIL", $uuser->email);

      $e_message = $tpl->parse("MAIL", "mail");
      $e_message = textwrap($e_message, 78, "\n");

      mailfrom("followup-" . $track['aid'] . "@" . $bounce_host,
	$uuser->email, $e_message);
    }
  }

  if (!isset($_page) || empty($_page))
    $tpl->set_var("refresh_page", "");

  /* FIXME: Dumb workaround */
  unset($tpl->varkeys["PAGE"]);
  unset($tpl->varvals["PAGE"]);

  $_domain = $tpl->get_var("DOMAIN");
  unset($tpl->varkeys["DOMAIN"]);
  unset($tpl->varvals["DOMAIN"]);

  $tpl->set_var(array(
    "MSG_MID" => $mid,
    "PAGE" => $_page,
    "DOMAIN" => $_domain,
    "form" => "",
  ));
}

$tpl->parse("PREVIEW", "message");
$tpl->pparse("CONTENT", "post");
?>
