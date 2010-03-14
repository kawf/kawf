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
require_once("thread.inc");
require_once("message.inc");
require_once("page-yatt.inc.php");

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
$tpl->set_block("error", "video");
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
  "video",
  "subject_req",
  "subject_change",
  "subject_too_long",
);

$tpl->set_var("FORUM_NOTICES", "");

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->parse("FORUM_HEADER", "forum_header");

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

      print generate_page('Post Message Denied',$tpl->parse("CONTENT", "nonewthreads"));
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

      print generate_page('Post Message Denied',$tpl->parse("CONTENT", "noreplies"));
      exit;
    }
  }
}

$tpl->set_var("disabled", "");

if ($_POST['tid']) {
  $thread = get_thread($_POST['tid']);

  if (isset($thread['flag']['Locked']) && !$user->capable($forum['fid'], 'Lock')) {
    $tpl->set_var(array(
      "error" => "",
      "preview" => "",
      "duplicate" => "",
      "form" => "",
      "accept" => "",
    ));

    print generate_page('Post Message Denied', $tpl->parse("CONTENT", "locked"));
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
/* TZ: format to viewing user's local time */
$tzoff = isset($user->tzoff)?$user->tzoff:0;
$msg['date'] = strftime("%Y-%m-%d %H:%M:%S", time() - $tzoff);
$msg['ip'] = $remote_addr;
$msg['aid'] = $user->aid;
$msg['flags'] = 'NewStyle';

if (isset($_POST['postcookie'])) {
  if ($_POST['preview'])
    $preview = 1;

  if ($_POST['imgpreview'])
    $imgpreview = 1;

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

  preprocess($msg, $_POST);

  /* find parent for "Re: */
  if (isset($msg['pmid'])) {
    $index = find_msg_index($msg['pmid']);
    if (isset($index)) {
      $sql = "select * from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($msg['pmid']) . "'";
      $result = mysql_query($sql) or sql_error($sql);

      if (mysql_num_rows($result))
        $parent = mysql_fetch_assoc($result);
    }
  }

  if (empty($msg['subject']) && strlen($msg['subject']) == 0) {
    $error["subject_req"] = true;
  } elseif (isset($parent) && $msg['subject'] == "Re: " . $parent['subject'] &&
    empty($msg['message']) && strlen($msg['message']) == 0 &&
    empty($msg['url'])) {
    $error["subject_change"] = true;
  } elseif (strlen($msg['subject']) > 100) {
    $error["subject_too_long"] = true;
    $msg['subject'] = substr($msg['subject'], 0, 100);
  }

  /* first time around, there is an imageurl set, and the user
   did not preview, force the action to "preview" */
  if ((!empty($msg['imageurl']) || !empty($msg['video']))
    && !isset($imgpreview)) {
    $preview = 1;
  }

  if ((isset($error) || isset($preview))) {
    $imgpreview = 1;
    if(!empty($msg['imageurl']))
      $error["image"] = true;

    if(!empty($msg['video']))
      $error["video"] = true;
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
  $msg['url'] = $msg['urltext'] = $msg['imageurl'] = $msg['video'] = "";

  /* Guaranteed no picture */
  $tpl->set_var("image", "");

  /* allow pid to come from _POST or _GET */
  if (isset($_REQUEST['pid'])) {
    /* Grab the actual message */
    $index = find_msg_index($pid);
    $sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($pid) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    $pmsg = mysql_fetch_assoc($result);

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
  require_once("postmessage.inc");

  /* sets $msg['mid'] to the new message id */
  if (postmessage($user, $indexes, $forum['fid'], $msg, $_POST) == true)
    $tpl->set_var("duplicate", "");

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

    while ($track = mysql_fetch_assoc($result)) {
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

print generate_page('Post Message', $tpl->parse("CONTENT", "post"));
?>
