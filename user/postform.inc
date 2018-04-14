<?php
function hidden($name, $value)
{
  return "<input type=\"hidden\" name=\"" . htmlspecialchars($name) . "\" "
  	. "value=\"" . htmlspecialchars($value) . "\">\n";
}

function render_postform($tpl, $action, $user, $msg=null, $imgpreview=false)
{
    global $thread, $forum, $script_name, $path_info;
    global $tthreads_by_tid;
    global $_page;	/* hack */
    global $Debug;

    $debug = "\naction = $action\n";
    $debug .= "imgpreview = $imgpreview\n";
    $debug .= "_REQUEST:\n";
    foreach ($_REQUEST as $k => $v) {
      if (!is_numeric($k) && is_scalar($v))
	$debug.=" $k => $v\n";
    }

    if ($Debug) {
      if (isset($msg)) {
	$debug .= "msg:\n";
	foreach ($msg as $k => $v) {
	  if (!is_numeric($k) && is_scalar($v))
	    $debug.=" $k => $v\n";
	}
      }
      $debug = str_replace("--","- -", $debug);
      $tpl->set_var("POSTFORM_DEBUG", "<!-- $debug -->");
    } else {
      $tpl->set_var("POSTFORM_DEBUG", "");
    }

    $tpl->set_file("postform", "postform.tpl");

    $tpl->set_block("postform", "disabled");
    $tpl->set_block("postform", "enabled");

    $tpl->set_block("disabled", "nonewthreads");
    $tpl->set_block("disabled", "noreplies");
    $tpl->set_block("disabled", "locked");

    $tpl->set_block("enabled", "acct");
    $tpl->set_block("enabled", "noacct");

    $tpl->set_block("acct", "offtopic");
    $tpl->set_block("acct", "imageupload");

    if (isset($thread) && !isset($forum['option']['PostReply']) && !$user->capable($forum['fid'], 'Delete')) {
      $tpl->set_var(array(
	"enabled" => "",
	"locked" => "",
	"nonewthreads" => "",
	"imageupload" => "",
      ));
    } else if (!isset($thread) && !isset($forum['option']['PostThread']) && !$user->capable($forum['fid'], 'Delete')) {
      $tpl->set_var(array(
	"enabled" => "",
	"locked" => "",
	"noreplies" => "",
	"imageupload" => "",
      ));
    } else if (isset($thread) && isset($thread['flag']['Locked']) && !$user->capable($forum['fid'], 'Lock')) {
      $tpl->set_var(array(
	"enabled" => "",
	"nonewthreads" => "",
	"noreplies" => "",
	"imageupload" => "",
      ));
    } else if (isset($user->aid)) {

      /**********************/
      /* hidden form values */
      /**********************/
      if (!isset($_REQUEST['postcookie']))
	$postcookie = md5("post" . microtime());
      else
	$postcookie = $_REQUEST['postcookie'];
      $hidden = hidden("postcookie", $postcookie);
      $hidden .= hidden("forumname", $forum['shortname']);

      if (isset($_page) && !empty($_page))
	$hidden .= hidden("page", $_page);
      else
	$hidden .= hidden("page", $script_name . $path_info);

      if ($imgpreview)
	$hidden .= hidden("imgpreview", 'true');
      if (isset($msg['mid'])) {
	$hidden .= hidden("mid", $msg['mid']);
	$tpl->set_var("SUBMITTEXT", "Update Message");
      } else {
	if (!isset($msg['pmid']))
	  $tpl->set_var("SUBMITTEXT", "Post New Thread");
	else
	  $tpl->set_var("SUBMITTEXT", "Post Reply");
      }

      if (isset($msg['pmid']))
	$hidden .= hidden("pmid", $msg['pmid']);
      if (isset($msg['tid']))
	$hidden .= hidden("tid", $msg['tid']);

      $tpl->set_var("HIDDEN", $hidden);

      /*********************/
      /* message and links */
      /*********************/
      $tpl->set_var("SUBJECT", escape_form($msg['subject']));

      if (isset($msg['message']))
	$tpl->set_var("MESSAGE", $msg['message']);
      else
	$tpl->set_var("MESSAGE", '');

      $tpl->set_var("URLLINK", escape_form_url($msg['url']));
      $tpl->set_var("URLTEXT", escape_form($msg['urltext']));
      $tpl->set_var("VIDEO", escape_form_url($msg['video']));
      $tpl->set_var("IMAGEURL", escape_form_url($msg['imageurl']));

      $tpl->set_var("USER_NAME", $user->name);
      $tpl->set_var("USER_EMAIL", $user->email);

      if (!can_upload_images() || isset($msg["mid"]))
	$tpl->set_var("imageupload", "");

      /***************/
      /* check boxes */
      /***************/
      if (isset($_REQUEST['preview']) || isset($_REQUEST['post'])) {
	/* preview/post - take values submitted by user */
	$offtopic = $_REQUEST['OffTopic'];
	$expose_email = $_REQUEST['ExposeEmail'];
	$email_followup = $_REQUEST['EmailFollowup'];
	$track_thread = isset($_REQUEST['TrackThread']);
      } else if (isset($msg['mid'])) {
	/* edit existing message - take values from old msg */
	$offtopic = ($msg['state']=="OffTopic");
	$expose_email = !empty($msg['email']);
	$email_followup = is_msg_etracked($msg);
	$track_thread = is_msg_tracked($msg);
      } else {
	/* new message - defaults */
	$offtopic = false;
	$expose_email = !isset($user->pref['SecretEmail']);
	$email_followup = false;
	/* if thread is already tracked, don't untrack it unless the user asks for it */
	$track_thread = is_thread_tracked($thread) || isset($user->pref['AutoTrack']);
      }

      /*************/
      /* overrides */
      /*************/
      /* forum doesn't allow offtopic, hide checkbox */
      if (!isset($forum['option']['OffTopic']))
	$tpl->set_var("offtopic", "");

      /* if offtopic is set, user can't unset w/o perms */
      if ($offtopic && !$user->capable($forum['fid'], 'OffTopic'))
	$tpl->set_var("offtopic", "");

      /* can't set email_followup if thread is not tracked */
      if (!$track_thread) $email_followup = false;

      $tpl->set_var(array(
	"OFFTOPIC" => $offtopic?" checked":"",
	"EXPOSEEMAIL" => $expose_email?" checked":"",
	"EMAILFOLLOWUP" => $email_followup?" checked":"",
	"TRACKTHREAD" => $track_thread?" checked":"",
	"ACTION" => $action,
	"MAXIMAGEFILEBYTES" => max_image_upload_bytes(),
	"noacct" => "",
	"disabled" => "",
      ));

      $tpl->set_var("token", $user->token());
      $tpl->parse("acct", "acct");	/* This fixed URL and PAGE for some reason */
    } else {
      $tpl->set_var(array(
	"acct" => "",
	"disabled" => "",
      ));
      $tpl->parse("noacct", "noacct");/* This fixed URL and PAGE for some reason */
    }

    $tpl->parse("FORM", "postform");
}
// vim: sw=2
?>
