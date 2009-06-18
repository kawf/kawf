<?php

$user->req();

if ($user->status != 'Active') {
  echo "Your account isn't validated\n";
  exit;
}

/* Check the data to make sure they entered stuff */
if (!isset($forum)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  Header("Location: http://$server_name$script_name$path_info/");
  exit;
}

require_once("textwrap.inc");
require_once("strip.inc");
require_once("message.inc");

$tpl->set_file(array(
  "post" => "post.tpl",
  "message" => "message.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
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

message_set_block($tpl);

$errors = array(
  "image",
  "subject_req",
  "subject_change",
  "subject_too_long",
);

$tpl->parse("FORUM_HEADER", "forum_header");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

if (isset($ad_generic)) {
  $urlroot = "/ads";
  /* We get our money from ads, make sure it's there */
  require_once("ads.inc");

  $ad = ads_view("$ad_generic,${ad_base}_" . $forum['shortname'], "_top");
  $tpl->_set_var("AD", $ad);
}

if (!$user->capable($forum['fid'], 'Delete')) {
  if (!isset($_POST['tid'])) {
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

if ($_POST['tid']) {
  $index = find_thread_index($_POST['tid']);
  $sql = "select * from f_threads" . $indexes[$index]['iid'] . " where tid = '" . addslashes($_POST['tid']) . "'";
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

if ($Debug) {
  $debug .= "_POST:\n";
  foreach ($_POST as $k => $v) {
    if (!is_numeric($k) && strlen($v)>0)
      $debug.=" $k => $v\n";
  }
  $debug = str_replace("--","- -", $debug);
  $tpl->set_var("DEBUG", "<!-- $debug -->");
} else {
  $tpl->set_var("DEBUG", "");
}

/* create brand new message */
$msg['date'] = strftime("%Y-%m-%d %H:%M:%S", time() - $user->tzoff);
$msg['ip'] = $remote_addr;
$msg['aid'] = $user->aid;
$msg['flags'] = 'NewStyle';

if (isset($_POST['postcookie'])) {
  $postcookie = $_POST['postcookie'];
  $preview = $_POST['preview'];
  $imgpreview = $_POST['imgpreview'];

  /* FIXME: Sanitize integers */
  $msg['mid'] = $_POST['mid'];
  $msg['pmid'] = $_POST['pmid'];
  $msg['tid'] = $_POST['tid'];

  /* Sanitize the strings */
  $msg['name'] = stripcrap($user->name);

  /* FIXME: bug 2771354 - dont throw away the email; just mark
     the message with some sort of flag to indicate hidden */
  if (isset($_POST['ExposeEmail']))
    $msg['email'] = stripcrap($user->email);
  else
    $msg['email'] = "";

  /* Strip any tags from the data */
  $msg['message'] = stripcrap($_POST['message'], $standard_tags);
  $msg['subject'] = stripcrap($_POST['subject'], $subject_tags);

  $msg['url'] = stripcrapurl($_POST['url']);
  if (!empty($msg['url']) && !preg_match("/^[a-z]+:\/\//i", $msg['url']))
    $msg['url'] = "http://".$msg['url'];

  $msg['urltext'] = stripcrap($_POST['urltext']);
  $msg['imageurl'] = stripcrapurl($_POST['imageurl']);

  if (!empty($msg['imageurl']) && !preg_match("/^[a-z]+:\/\//i", $msg['imageurl']))
    $msg['imageurl'] = "http://".$msg['imageurl'];

  if (isset($msg['pmid'])) {
    $index = find_msg_index($msg['pmid']);
    if (isset($index)) {
      $sql = "select * from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($msg['pmid']) . "'";
      $result = mysql_query($sql) or sql_error($sql);

      if (mysql_num_rows($result))
        $parent = mysql_fetch_array($result);
    }
  }

  if (empty($msg['subject']) && strlen($msg['subject']) == 0)
    $error["subject_req"] = true;
  elseif (isset($parent) && $msg['subject'] == "Re: " . $parent['subject'] && empty($msg['message']) && strlen($msg['message']) == 0 && empty($msg['url']))
    $error["subject_change"] = true;
  elseif (strlen($msg['subject']) > 100) {
    /* Subject is too long */
    $error["subject_too_long"] = true;
    $msg['subject'] = substr($msg['subject'], 0, 100);
  }

  if (!empty($msg['imageurl']) && !isset($imgpreview))
    $preview = 1;

  if ((isset($error) || isset($preview)) && !empty($msg['imageurl'])) {
    $imgpreview = 1;
    $error["image"] = true;
  }
 
  render_message($tpl, $msg, $user);

  if (isset($_POST['OffTopic']))
    $status = "OffTopic";
  else
    $status = "Active";

  $accepted = !isset($error);
} else {
  /* somebody hit post.phtml directly, just generate blank post form */
  $msg['message'] = $msg['subject'] = "";
  $msg['url'] = $msg['urltext'] = $msg['imageurl'] = "";

  /* Guaranteed no picture */
  $tpl->set_var("image", "");

  /* allow pid to come from _POST or _GET */
  if (isset($_REQUEST['pid'])) {
    /* Grab the actual message */
    $index = find_msg_index($pid);
    $sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($pid) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    $pmsg = mysql_fetch_array($result);

    /* munge subject line */
    if (preg_match("/^re:/i", $pmsg['subject'], $sregs))
      $msg['subject'] = $pmsg['subject'];
    /*
    else
      $msg['subject'] = "Re: " . $pmsg['subject'];
    */   
  }
}

if (!isset($preview))
  $tpl->set_var("preview", "");
else
  $tpl->set_var("owner", "");

if (isset($error)) {
  foreach ($errors as $n => $e) {
    if (!isset($error[$e]))
      $tpl->set_var($e, "");
  }
} else
  $tpl->set_var("error", "");
    
if (!$accepted || isset($preview)) {
  require_once("postform.inc");
  render_postform($tpl, "post", $user, $msg, $imgpreview);

  $tpl->set_var(array(
    "accept" => "",
    "duplicate" => "",
  ));
} else {
  $flags[] = "NewStyle";

  if (empty($msg['message']) && strlen($msg['message']) == 0)
    $flags[] = "NoText";

  if (!empty($msg['url']) || preg_match("/<[[:space:]]*a[[:space:]]+href/i", $msg['message']))
    $flags[] = "Link";

  if (!empty($msg['imageurl']) || preg_match("/<[[:space:]]*img[[:space:]]+src/i", $msg['message']))
    $flags[] = "Picture";

  $msg['flags'] = implode(",", $flags);

  /* prepend image url to new message for entry into the db */
  if (!empty($msg['imageurl']))
    $msg['message'] = "<center><img src=\"" . $msg['imageurl'] . "\"></center><p>" . $msg['message'];

  /* Add it into the database */
  /* Check to make sure this isn't a duplicate */
  $sql = "insert into f_dupposts ( cookie, fid, aid, ip, tstamp ) values ('" . addslashes($postcookie) . "', " . $forum['fid'] . ", " . $user->aid . ", '" . addslashes($msg['ip']) . "', NOW() )";
  $result = mysql_query($sql);

  if (!$result) {
    if (mysql_errno() != 1062)
      sql_error($sql);

    $msg['mid'] = sql_query1("select mid from f_dupposts where cookie = '" . addslashes($postcookie) . "'");
  } else {
    /* Grab a new mid, this should work reliably */
    do {
      $sql = "select max(id) + 1 from f_unique where fid = " . $forum['fid'] . " and type = 'Message'";
      $result = mysql_query($sql) or sql_error($sql);

      list ($msg['mid']) = mysql_fetch_row($result);

      $sql = "insert into f_unique ( fid, type, id ) values ( " . $forum['fid'] . ", 'Message', ". $msg['mid'] . ")";
      $result = mysql_query($sql);
    } while (!$result && mysql_errno() == 1062);

    if (!$result)
      sql_error($sql);

    $newmessage = 1;

    sql_query("update f_dupposts set mid = " . $msg['mid'] . " where cookie = '" . addslashes($postcookie) . "'");
  }

  /* Add the message to the last index */
  $index = end($indexes);

  $mtable = "f_messages" . $index['iid'];
  $ttable = "f_threads" . $index['iid'];

  if (!isset($newmessage)) {
    $omsg = sql_querya("select * from $mtable where mid = '" . addslashes($msg['mid']) ."'");
    $sql = "update $mtable set " .
	"name = '" . addslashes($msg['name']) . "', " .
	"email = '" . addslashes($msg['email']) . "', " .
	"ip = '" . addslashes($msg['ip']) . "', " .
	"flags = '" . $msg['flags'] . "', " .
	"subject = '" . addslashes($msg['subject']) . "', " .
	"message = '" . addslashes($msg['message']) . "', " .
	"url = '" . addslashes($msg['url']) . "', " .
	"urltext = '" . addslashes($msg['urltext']) . "', " .
	"state = '$status' " .
	"where mid = '" . addslashes($msg['mid']) . "' and state = 'Active'";
  } else
    $sql = "insert into $mtable " .
	"( mid, aid, pid, tid, name, email, date, ip, flags, subject, message, url, urltext, state ) values ( '"
	    . addslashes($msg['mid']) . "', '"
	    . addslashes($user->aid) . "', '"
	    . addslashes($msg['pmid']) . "', '"
	    . addslashes($msg['tid']) . "', '"
	    . addslashes($msg['name']) . "', '"
	    . addslashes($msg['email']) . "', NOW(), '"
	    . addslashes($msg['ip']) . "', '"
	    . $msg['flags'] . "', '"
	    . addslashes($msg['subject']) . "', '"
	    . addslashes($msg['message']) . "', '"
	    . addslashes($msg['url']) . "', '"
	    . addslashes($msg['urltext']) ."', '$status' );";

  $result = mysql_query($sql) or sql_error($sql);

  if (isset($newmessage)) {
    if (!$msg['pmid']) {
      /* Grab a new tid, this should work reliably */
      do {
        $sql = "select max(id) + 1 from f_unique where fid = " . $forum['fid'] . " and type = 'Thread'";
        $result = mysql_query($sql) or sql_error($sql);

        list ($msg['tid']) = mysql_fetch_row($result);

        $sql = "insert into f_unique ( fid, type, id ) values ( " . $forum['fid'] . ", 'Thread', " . $msg['tid'] . " )";
        $result = mysql_query($sql);
      } while (!$result && mysql_errno() == 1062);

      if (!$result)
        sql_error($sql);

      $sql = "update $mtable set tid = " . $msg['tid'] . " where mid = " . $msg['mid'];
      mysql_query($sql) or sql_error($sql);

      $sql = "insert into $ttable ( tid, mid, tstamp ) values ( " . $msg['tid']  .", " . $msg['mid'] . ", NOW() )";
      mysql_query($sql) or sql_error($sql);

      $sql = "update f_indexes set maxtid = " . $msg['tid'] . " where iid = " . $index['iid'] . " and maxtid < " . $msg['tid'];
      mysql_query($sql) or sql_error($sql);
    } else {
      $sql = "update $ttable set replies = replies + 1, tstamp = NOW() where tid = '" . addslashes($msg['tid']) . "'";
      mysql_query($sql) or sql_error($sql);
    }

    $sql = "update f_indexes set maxmid = " . $msg['mid'] . " where iid = " . $index['iid'] . " and maxmid < " . $msg['mid'];
    mysql_query($sql) or sql_error($sql);

    if (!$msg['pmid']) {
      $sql = "update f_indexes set $status = $status + 1 where iid = " . $index['iid'];
      mysql_query($sql) or sql_error($sql);
    }

    $user->post($forum['fid'], $status, 1);
    $tpl->set_var("duplicate", "");
  } else {
    $user->post($forum['fid'], $omsg['state'], -1);

    if (!$msg['pmid'])
      sql_query("update f_indexes set " . $omsg['state'] . " = " . $omsg['state'] . " - 1 where iid = " . $index['iid']);
  }

  $sql = "insert into f_updates ( fid, mid ) values ( " . $forum['fid'] . ", '" . addslashes($msg['mid']) . "' )";
  mysql_query($sql);

  if (!empty($_POST['TrackThread']) && isset($newmessage)) {
    $options = "";

    if (isset($_POST['EmailFollowup']))
      $options = "SendEmail";

    $sql = "select * from f_tracking where fid = " . $forum['fid'] . " and aid = '" . $user->aid . "' and tid = '" . addslashes($msg['tid']) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    if (!mysql_num_rows($result)) {
      $sql = "insert into f_tracking ( fid, tid, aid, options ) values ( " . $forum['fid'] . ", '" . addslashes($msg['tid']) . "', '" . addslashes($user->aid) . "', '$options' )";
      mysql_query($sql) or sql_error($sql);
    }
  }

  require_once("mailfrom.inc");

  $sql = "select * from f_tracking where fid = " . $forum['fid'] . " and tid = '" . addslashes($msg['tid']) . "' and options = 'SendEmail' and aid != " . $user->aid;
  $result = mysql_query($sql) or sql_error($sql);

  if (mysql_num_rows($result) > 0) {
    # This is needed since $index may be trashed --jerdfelt
    $index = find_msg_index($thread['mid']);
    $sql = "select subject from f_messages" . $indexes[$index]['iid'] . " where mid = " . $thread['mid'];
    $res2 = mysql_query($sql) or sql_error($sql);

    list($t_subject) = mysql_fetch_row($res2);

    $e_message = substr($msg['message'], 0, 1024);
    if (strlen($msg['message']) > 1024) {
      $bytes = strlen($msg['message']) - 1024;
      $plural = ($bytes == 1) ? '' : 's';
      $e_message .= "...\n\nMessage continues for another $bytes byte$plural\n";
    }

    $tpl->set_var(array(
      "THREAD_SUBJECT" => $t_subject,
      "USER_NAME" => $user->name,
      "HOST" => $_url,
      "FORUM_NAME" => $forum['name'],
      "FORUM_SHORTNAME" => $forum['shortname'],
      "MSG_MID" => $msg['mid'],
      "MAIL_MSG_SUBJECT" => $msg['subject'],
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

  /* $_page set by main.php from $_REQUEST */
  if (!isset($_page) || empty($_page))
    $tpl->set_var("refresh_page", "");

  /* FIXME: Dumb workaround */
  /* ??? why are we not getting $_page from $tpl here, like we do for $_domain
   * here and $_page in showforum and tracking? */
  unset($tpl->varkeys["PAGE"]);
  unset($tpl->varvals["PAGE"]);

  $_domain = $tpl->get_var("DOMAIN");
  unset($tpl->varkeys["DOMAIN"]);
  unset($tpl->varvals["DOMAIN"]);

  $tpl->set_var(array(
    "MSG_MID" => $msg['mid'],
    "PAGE" => $_page,
    "DOMAIN" => $_domain,
    "form" => "",
  ));
}

$tpl->parse("PREVIEW", "message");
$tpl->pparse("CONTENT", "post");
?>
