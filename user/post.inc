<?php
function hidden($name, $value)
{
  return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
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
  if (!isset($postcookie))
    $postcookie = md5("post" . microtime());
  $hidden = hidden("postcookie", $postcookie);
  $hidden .= hidden("forumname", $forum['shortname']);

  if (isset($_page) && !empty($_page))
    $hidden .= hidden("page", $_page);
  else
    $hidden .= hidden("page", $SCRIPT_NAME . $PATH_INFO);

  if (isset($imgpreview))
    $hidden .= hidden("imgpreview", 'true');
  if (isset($mid)) {
    $hidden .= hidden("mid", $mid);
    $tpl->set_var("SUBMITTEXT", "Update Message");
  } else {
    if (!isset($pmid))
      $tpl->set_var("SUBMITTEXT", "Post new thread");
    else
      $tpl->set_var("SUBMITTEXT", "Post reply");
  }

  if (isset($pmid))
    $hidden .= hidden("pmid", $pmid);
  if (isset($tid))
    $hidden .= hidden("tid", $tid);

  $tpl->set_var("HIDDEN", $hidden);

  if (!isset($subject))
    $subject = "";
  $subject = preg_replace("/&lt;/", "<", $subject);
  $subject = preg_replace("/&gt;/", ">", $subject);
  $subject = preg_replace("/\"/", "&quot;", $subject);
  $tpl->set_var("SUBJECT", $subject);

  if (!isset($message))
    $message = "";
  // $message = preg_replace("/&lt;/", "<", $message);
  // $message = preg_replace("/&gt;/", ">", $message);
  $tpl->set_var("MESSAGE", $message);

  if (!isset($url))
    $url = "";
  $url = preg_replace("/&/", "&amp;", $url);
  $url = preg_replace("/\"/", "&quot;", $url);
  $tpl->set_var("URLLINK", $url);

  if (!isset($urltext))
    $urltext = "";
  $urltext = preg_replace("/&lt;/", "<", $urltext);
  $urltext = preg_replace("/&gt;/", ">", $urltext);
  $tpl->set_var("URLTEXT", $urltext);

  if (!isset($imageurl))
    $imageurl = "";
  $imageurl = preg_replace("/&amp;/", "&", $imageurl);
  $imageurl = preg_replace("/\"/", "&quot;", $imageurl);
  $tpl->set_var("IMAGEURL", $imageurl);

  $tpl->set_var("USER_NAME", $user->name);
  $tpl->set_var("USER_EMAIL", $user->email);

  if (isset($preview) || isset($post))
    $checked = isset($ExposeEmail) && $ExposeEmail;
  else
    $checked = !isset($user->pref['SecretEmail']);
  if ($checked)
    $tpl->set_var("EXPOSEEMAIL", " checked");
  else
    $tpl->set_var("EXPOSEEMAIL", "");

  if (!isset($forum['opt.OffTopic']))
    $tpl->set_var("offtopic", "");
  else {
    if (isset($preview) || isset($post))
      $checked = isset($OffTopic) && $OffTopic;
    else
      $checked = 0;
    if ($checked)
      $tpl->set_var("OFFTOPIC", " checked");
    else
      $tpl->set_var("OFFTOPIC", "");
  }

  $tpl->set_var("EMAILFOLLOWUP", "");

  if (isset($preview) || isset($post))
    $checked = isset($TrackThread);
  else
    $checked = isset($user->pref['AutoTrack']) ||
               (isset($tid) && isset($tthreads_by_tid[$tid]));
  if ($checked)
    $tpl->set_var("TRACKTHREAD", " checked");
  else
    $tpl->set_var("TRACKTHREAD", "");

  $tpl->set_var(array(
    "ACTION" => $action,
    "noacct" => "",
    "disabled" => "",
  ));
  $tpl->parse("acct", "acct");	/* This fixed URL and PAGE for some reason */
} else {
  $tpl->set_var(array(
    "acct" => "",
    "disabled" => "",
  ));
  $tpl->parse("noacct", "noacct");/* This fixed URL and PAGE for some reason */
}

$tpl->parse("FORM", "postform");
?>
