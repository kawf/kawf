<?php
require_once("textwrap.inc");

function print_collapsed($thread, $msg, $replies)
{
  global $user, $forum, $tpl;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = "true";
  }

  $string = "<li>";

  $msg['subject']=softbreaklongwords($msg['subject'],40);

  if (isset($user->pref['FlatThread']))
    $string .= "<a href=\"/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
  else
    $string .= "<a href=\"/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";

  if (isset($flags['NoText'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/nt.gif\" alt=\"no text\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/pic.gif\" alt=\"contains picture\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/url.gif\" alt=\"contains url\">";
    else
      $string .= " (link)";
  }

  if ($msg['state'] == 'OffTopic' && !isset($user->pref['SimpleHTML']))
    $string .= " <img src=\"/pics/ot.gif\" alt=\"off topic\">";

  $string .= "&nbsp;&nbsp;-&nbsp;&nbsp;<b>" . $msg['name'] . "</b>&nbsp;&nbsp;<font size=\"-2\"><i>" . $msg['date'] . "</i>";

  $string .= " ($replies " . ($replies == 1 ? "reply" : "replies") . ")";

  $string .= "</font>";

  if (isset($thread['flag.Locked']) && !$msg['pmid']) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/lock.gif\" alt=\"locked\">";
    else
      $string .= " (locked)";
  }

  if ($msg['state'] == 'OffTopic' && isset($user->pref['SimpleHTML']))
    $string .= " (OffTopic)";

  if ($msg['state'] != "Active" && $msg['state'] != "OffTopic")
    $string .= " (" . $msg['state'] . ")";

  $page = $tpl->get_var("PAGE");

  $stoken = $user->token();
  switch ($msg['state']) {
  case "Moderated":
    if ($user->capable($forum['fid'], 'Moderate'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\">um</a>";
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Deleted&amp;mid=" . $msg['mid'] . "\">dm</a>";
    break;
  case "OffTopic":
    if ($user->capable($forum['fid'], 'OffTopic'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\">uo</a>";
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Deleted&amp;mid=" . $msg['mid'] . "\">dm</a>";
    break;
  case "Deleted":
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\">ud</a>";
    break;
  case "Active":
    if ($user->capable($forum['fid'], 'OffTopic'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=OffTopic&amp;mid=" . $msg['mid'] . "\">om</a>";
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Deleted&amp;mid=" . $msg['mid'] . "\">dm</a>";
    break;
  }

  if ($user->capable($forum['fid'], 'Lock') && !$msg['pmid']) {
    if (isset($thread['flag.Locked']))
      $string .= " <a href=\"/" . $forum['shortname'] . "/unlock.phtml?tid=" . $msg['tid'] . "&amp;page=$page&amp;token=$stoken\">ul</a>";
    else
      $string .= " <a href=\"/" . $forum['shortname'] . "/lock.phtml?tid=" . $msg['tid'] . "&amp;page=$page&amp;token=$stoken\">lt</a>";
  }

  $string .= "</li>\n";

  return $string;
}

?>
