<?php
function hidden($name, $value)
{
  return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
}

if (isset($user)) {
  $postcookie = md5("post" . microtime());
  $hidden = hidden('postcookie', $postcookie);
  $hidden .= hidden('forumname', $forum['shortname']);

  if (isset($imgpreview))
    $hidden .= hidden('imgpreview', 'true');
  if (isset($mid)) {
    $hidden .= hidden('mid', $mid);
    $tpl->assign(SUBMITTEXT, 'Update Message');
  } else {
    if (!isset($pid))
      $tpl->assign(SUBMITTEXT, 'Post new thread');
    else
      $tpl->assign(SUBMITTEXT, 'Post reply');
  }

  if (isset($pid))
    $hidden .= hidden('pid', $pid);
  if (isset($tid))
    $hidden .= hidden('tid', $tid);

  $tpl->assign(HIDDEN, $hidden);

  if (!isset($subject))
    $subject = "";
  $tpl->assign(SUBJECT, ereg_replace("\"", "&quot;", $subject));

  if (!isset($message))
    $message = "";
  $tpl->assign(MESSAGE, $message);

  if (!isset($url))
    $url = "";
  $tpl->assign(URL, $url);

  if (!isset($urltext))
    $urltext = "";
  $tpl->assign(URLTEXT, $urltext);

  if (!isset($imageurl))
    $imageurl = "";
  $tpl->assign(IMAGEURL, $imageurl);

  $tpl->assign(USER_NAME, $user['name']);
  $tpl->assign(USER_EMAIL, $user['email']);

  $checked = isset($ExposeEmail) ? $ExposeEmail : !isset($user['prefs.SecretEmai']);
  if ($checked)
    $tpl->assign(EXPOSEEMAIL, ' checked');
  else
    $tpl->assign(EXPOSEEMAIL, '');

  $tpl->assign(EMAILFOLLOWUP, '');

  $checked = isset($TrackThread) ? $TrackThread : isset($user['prefs.AutoTrack']);
  if ($checked)
    $tpl->assign(TRACKTHREAD, ' checked');
  else
    $tpl->assign(TRACKTHREAD, '');

  $tpl->assign(DIRECTORY, $directory);

  $tpl->parse(POSTFORM, 'postform');
} else
  $tpl->parse(POSTFORM, 'postform_noacct');

?>
