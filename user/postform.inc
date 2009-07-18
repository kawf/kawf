<?php
function hidden($name, $value)
{
  return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
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
      if (!is_numeric($k) && strlen($v)>0)
	$debug.=" $k => $v\n";
    }

    if ($Debug) {
      if (isset($msg)) {
	$debug .= "msg:\n";
	foreach ($msg as $k => $v) {
	  if (!is_numeric($k) && strlen($v)>0)
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

    if (isset($thread) && !isset($forum['opt.PostReply']) && !$user->capable($forum['fid'], 'Delete')) {
      $tpl->set_var(array(
	"enabled" => "",
	"locked" => "",
	"nonewthreads" => "",
      ));
    } else if (!isset($thread) && !isset($forum['opt.PostThread']) && !$user->capable($forum['fid'], 'Delete')) {
      $tpl->set_var(array(
	"enabled" => "",
	"locked" => "",
	"noreplies" => "",
      ));
    } else if (isset($thread) && isset($thread['flag.Locked']) && !$user->capable($forum['fid'], 'Lock')) {
      $tpl->set_var(array(
	"enabled" => "",
	"nonewthreads" => "",
	"noreplies" => "",
      ));
    } else if (isset($user->aid)) {
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

      if (!isset($msg['subject']))
	$subject = "";
      else
	$subject = $msg['subject'];
      $subject = preg_replace("/&lt;/", "<", $subject);
      $subject = preg_replace("/&gt;/", ">", $subject);
      $subject = preg_replace("/\"/", "&quot;", $subject);
      $tpl->set_var("SUBJECT", $subject);

      if (!isset($msg['message']))
	$message = "";
      else
	$message = $msg['message'];
      // $message = preg_replace("/&lt;/", "<", $message);
      // $message = preg_replace("/&gt;/", ">", $message);
      $tpl->set_var("MESSAGE", $message);

      if (!isset($msg['url']))
	$url = "";
      else
	$url = $msg['url'];
      $url = preg_replace("/&/", "&amp;", $url);
      $url = preg_replace("/\"/", "&quot;", $url);
      $tpl->set_var("URLLINK", $url);

      if (!isset($msg['urltext']))
	$urltext = "";
      else
	$urltext = $msg['urltext'];
      $urltext = preg_replace("/&lt;/", "<", $urltext);
      $urltext = preg_replace("/&gt;/", ">", $urltext);
      $urltext = preg_replace("/\"/", "&quot;", $urltext);
      $tpl->set_var("URLTEXT", $urltext);

      if (!isset($msg['imageurl']))
	$imageurl = "";
      else
	$imageurl = $msg['imageurl'];
      $imageurl = preg_replace("/&amp;/", "&", $imageurl);
      $imageurl = preg_replace("/\"/", "&quot;", $imageurl);
      $tpl->set_var("IMAGEURL", $imageurl);

      $tpl->set_var("USER_NAME", $user->name);
      $tpl->set_var("USER_EMAIL", $user->email);

      if (isset($_REQUEST['preview']) || isset($_REQUEST['post']))
	$checked = $_REQUEST['ExposeEmail'];
      else
	$checked = !isset($user->pref['SecretEmail']);
      if ($checked)
	$tpl->set_var("EXPOSEEMAIL", " checked");
      else
	$tpl->set_var("EXPOSEEMAIL", "");

      if (!isset($forum['opt.OffTopic']))
	$tpl->set_var("offtopic", "");
      else {
	if (isset($_REQUEST['preview']) || isset($_REQUEST['post']))
	  $checked = $_REQUEST['OffTopic'];
	else
	  $checked = 0;
	if ($checked)
	  $tpl->set_var("OFFTOPIC", " checked");
	else
	  $tpl->set_var("OFFTOPIC", "");
      }

      if (isset($_REQUEST['preview']) || isset($_REQUEST['post']))
	$checked = $_REQUEST['EmailFollowup'];
      else
	$checked = 0;
      if ($checked)
	$tpl->set_var("EMAILFOLLOWUP", " checked");
      else
	$tpl->set_var("EMAILFOLLOWUP", "");

      if (isset($_REQUEST['preview']) || isset($_REQUEST['post']))
	$checked = isset($_REQUEST['TrackThread']);
      else
	$checked = isset($user->pref['AutoTrack']) ||
		   (isset($msg['tid']) && isset($tthreads_by_tid[$msg['tid']]));
      if ($checked)
	$tpl->set_var("TRACKTHREAD", " checked");
      else
	$tpl->set_var("TRACKTHREAD", "");

      $tpl->set_var(array(
	"ACTION" => $action,
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
?>
