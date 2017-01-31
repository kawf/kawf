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
$tpl->set_block("error", "image_upload_failed");
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
  "image_upload_failed",
  "subject_change",
  "subject_too_long",
);

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->parse("FORUM_HEADER", "forum_header");

if (!$user->capable($forum['fid'], 'Delete')) {
  if (!isset($_POST['tid'])) {
    if (!isset($forum['option']['PostThread'])) {
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
    if (!isset($forum['option']['PostReply'])) {
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

if (is_numeric($_POST['tid'])) {
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
$msg['date'] = gen_date($user);
$msg['ip'] = $remote_addr;
$msg['aid'] = $user->aid;
$msg['flags'] = 'NewStyle';

if (isset($_POST['postcookie'])) {
  if ($_POST['preview'])
    $preview = 1;

  if ($_POST['imgpreview'])
    $imgpreview = 1;

  /* FIXME: Sanitize integers */
  if (is_numeric($_POST['mid'])) $msg['mid'] = $_POST['mid'];
  if (is_numeric($_POST['pmid'])) $msg['pmid'] = $_POST['pmid'];
  if (is_numeric($_POST['tid'])) $msg['tid'] = $_POST['tid'];

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
    $iid = mid_to_iid($msg['pmid']);
    if (!isset($iid)) throw new RuntimeException("no iid for pmid " . $msg['pmid']);
    $sql = "select * from f_messages$iid where mid = ?";
    $parent = db_query_first($sql, array($msg['pmid']));
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

  /* if uploading an image, proxy it to the image host and replace our image url */
  if (!isset($error) && can_upload_images() && isset($_FILES["imagefile"]) &&
  $_FILES["imagefile"]["size"] > 0) {
    $newimageurls = get_uploaded_image_urls($_FILES["imagefile"]["tmp_name"]);

    if ($newimageurls) {
      $msg["imageurl"] = $newimageurls[0];
      $msg["imagedeleteurl"] = $newimageurls[1];
    } else {
      $error["image_upload_failed"] = true;
    }
  } else {
    /* first time around, there is an imageurl set, and the user
     did not preview, force the action to "preview" */
    if ((!empty($msg['imageurl']) || !empty($msg['video'])) && !isset($imgpreview)) {
      $preview = 1;
    }
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

  /* allow pmid to come from _POST or _GET, either as pid or pmid, 
     and populate hidden inputs in form with tid and pmid */
  if (isset($_REQUEST['pid']) || isset($_REQUEST['pmid'])) {
    /* Grab the actual message */
    if (is_numeric($_REQUEST['pmid'])) $pmid = $_REQUEST['pmid'];
    else if (is_numeric($_REQUEST['pid'])) $pmid = $_REQUEST['pid'];

    if (!isset($pmid)) throw new RuntimeException("invalid pmid");

    /* get requested parent message */
    $iid = mid_to_iid($pmid);
    if (!isset($iid)) throw new RuntimeException("no iid for pmid $pmid");
    $sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from f_messages$iid where mid = ?";
    $pmsg = db_query_first($sql, array($pmid));

    /* grab tid and pmid from parent */
    $msg['tid'] = $pmsg['tid'];
    $msg['pmid'] = $pmsg['mid'];

    /* munge subject line from parent */
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
  if (postmessage($user, $forum['fid'], $msg, $_POST) == true)
    $tpl->set_var("duplicate", "");

  require_once("mailfrom.inc");

  $sql = "select * from f_tracking where fid = ? and tid = ? and options = 'SendEmail' and aid != ?";
  $sth = db_query($sql, array($forum['fid'], $msg['tid'], $user->aid));
  $track = $sth->fetch();

  if ($track) {
    $iid = mid_to_iid($thread['mid']);
    if (!isset($iid)) throw new RuntimeException("no iid for thread mid " . $thread['mid']);
      
    $sql = "select subject from f_messages$iid where mid = ?";
    $row = db_query_first($sql, array($thread['mid']));

    list($t_subject) = $row;

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

    do {
      $uuser = new ForumUser($track['aid']);

      $tpl->set_var("EMAIL", $uuser->email);

      $e_message = $tpl->parse("MAIL", "mail");
      $e_message = textwrap($e_message, 78, "\n");

      mailfrom("followup-" . $track['aid'] . "@" . $bounce_host,
	$uuser->email, $e_message);
    } while ($track = $sth->fetch());
  }
  $sth->closeCursor();

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
// vim: sw=2
?>
