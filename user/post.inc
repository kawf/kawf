<?php
function hidden($name, $value)
{
  return "<input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
}

$tpl->define_dynamic('acct', 'postform');
$tpl->define_dynamic('noacct', 'postform');

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
  $subject = ereg_replace("&", "&amp;", $subject);
  $subject = ereg_replace("\"", "&quot;", $subject);
  $tpl->assign(SUBJECT, $subject);

  if (!isset($message))
    $message = "";
  $message = ereg_replace("&", "&amp;", $message);
  $tpl->assign(MESSAGE, $message);

  if (!isset($url))
    $url = "";
  $url = ereg_replace("&", "&amp;", $url);
  $tpl->assign(URL, $url);

  if (!isset($urltext))
    $urltext = "";
  $urltext = ereg_replace("&", "&amp;", $urltext);
  $tpl->assign(URLTEXT, $urltext);

  if (!isset($imageurl))
    $imageurl = "";
  $imageurl = ereg_replace("&", "&amp;", $imageurl);
  $tpl->assign(IMAGEURL, $imageurl);

  $tpl->assign(USER_NAME, $user['name']);
  $tpl->assign(USER_EMAIL, $user['email']);

  if (isset($preview) || isset($post))
    $checked = isset($ExposeEmail);
  else
    $checked = !isset($user['prefs.SecretEmail']);
  if ($checked)
    $tpl->assign(EXPOSEEMAIL, ' checked');
  else
    $tpl->assign(EXPOSEEMAIL, '');

  $tpl->assign(EMAILFOLLOWUP, '');

  if (isset($preview) || isset($post))
    $checked = isset($TrackThread);
  else
    $checked = isset($user['prefs.AutoTrack']) ||
               (isset($tid) && isset($tthreads_by_tid[$tid]));
  if ($checked)
    $tpl->assign(TRACKTHREAD, ' checked');
  else
    $tpl->assign(TRACKTHREAD, '');

  $tpl->assign(ACTION, $action);
  $tpl->clear_dynamic('noacct');
} else
  $tpl->clear_dynamic('acct');

$tpl->assign(URLROOT, $urlroot);

$tpl->parse(FORM, 'postform');
?>
