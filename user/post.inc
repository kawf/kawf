<?php
function hidden($name, $value)
{
  return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
}

$tpl->set_file("postform", "postform.tpl");

$tpl->set_block("postform", "acct");
$tpl->set_block("postform", "noacct");
$tpl->set_block("postform", "locked");

if (isset($thread) && isset($thread['flag.Locked']) && !$user->capable($forum['fid'], 'Lock')) {
  $tpl->set_var("acct", "");
  $tpl->set_var("noacct", "");
} else if (isset($user->aid)) {
  if (!isset($postcookie))
    $postcookie = md5("post" . microtime());
  $hidden = hidden("postcookie", $postcookie);
  $hidden .= hidden("forumname", $forum['shortname']);

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
  $subject = preg_replace("/&amp;/", "&", $subject);
  $subject = preg_replace("/\"/", "&quot;", $subject);
  $tpl->set_var("SUBJECT", $subject);

  if (!isset($message))
    $message = "";
  $message = preg_replace("/&lt;/", "<", $message);
  $message = preg_replace("/&gt;/", ">", $message);
  $message = preg_replace("/&amp;/", "&", $message);
  $tpl->set_var("MESSAGE", $message);

  if (!isset($url))
    $url = "";
  $url = preg_replace("/&/", "&amp;", $url);
  $tpl->set_var("URLLINK", $url);

  if (!isset($urltext))
    $urltext = "";
  $urltext = preg_replace("/&lt;/", "<", $urltext);
  $urltext = preg_replace("/&gt;/", ">", $urltext);
  $urltext = preg_replace("/&amp;/", "&", $urltext);
  $tpl->set_var("URLTEXT", $urltext);

  if (!isset($imageurl))
    $imageurl = "";
  $imageurl = preg_replace("/&amp;/", "&", $imageurl);
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

  if (isset($preview) || isset($post))
    $checked = isset($OffTopic) && $OffTopic;
  else
    $checked = 0;
  if ($checked)
    $tpl->set_var("OFFTOPIC", " checked");
  else
    $tpl->set_var("OFFTOPIC", "");

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

  $tpl->set_var("ACTION", $action);
  $tpl->set_var("noacct", "");
  $tpl->set_var("locked", "");
  $tpl->parse("acct", "acct");	/* This fixed URL and PAGE for some reason */
} else {
  $tpl->set_var("acct", "");
  $tpl->set_var("locked", "");
  $tpl->parse("noacct", "noacct");/* This fixed URL and PAGE for some reason */
}

$tpl->parse("FORM", "postform");
?>
