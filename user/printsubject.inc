<?php
require_once("message.inc");
require_once("textwrap.inc");

function print_subject($thread, $msg, $replies = -1, $collapse = false)
{
  global $vmid, $user, $tthreads_by_tid, $forum, $tpl;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    foreach ($flagexp as $flag)
      $flags[$flag] = true;
  }

  $new = is_msg_bumped($msg);

  if ($new)
    $string .= "<i><b>";

  $msg['subject']=softbreaklongwords($msg['subject'],40);

  $nt = "";
  if (isset($flags['NewStyle']) && isset($flags['NoText']) && count($flags)==2)
    $nt = " class=\"nt\"";

  if (isset($vmid) && $vmid == $msg['mid'])
    $string .= "<span class=\"vmid\">" . $msg['subject'] . "</span>";
  else {
    if (isset($user->pref['FlatThread']))
      $string .= "<a href=\"/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\"" . $nt . ">" . $msg['subject'] . "</a>";
    else
      $string .= "<a href=\"/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\"" . $nt . ">" . $msg['subject'] . "</a>";
  }

  if ($new)
    $string .= "</b></i>";

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

  if (isset($flags['Video'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/video.gif\" alt=\"contains video\">";
    else
      $string .= " (vid)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/url.gif\" alt=\"contains url\">";
    else
      $string .= " (link)";
  }

  if ($msg['state'] == 'OffTopic' && !isset($user->pref['SimpleHTML']))
    $string .= " <img src=\"/pics/ot.gif\" alt=\"off topic\">";

  $string .= "&nbsp;&nbsp;-&nbsp;&nbsp;";
  $string .= "<span class=\"username\">" . $msg['name'] . "</span>&nbsp;&nbsp;";
  $string .= "<span class=\"threadinfo\">";
  $string .= "<i>" . $msg['date'] . "</i>";

  if ($replies > 0)
    $string .= " ($replies " . ($replies == 1 ? "reply" : "replies") . ($collapse?" hidden":"") . ")";

  if ($msg['views']>0)
    $string .= " (" . $msg['views'] . " view" . ($msg['views'] == 1 ? "" : "s") . ")";

  $string .= "</span>\n";

  if (isset($thread['flag']['Sticky']) && !$msg['pmid']) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " (<font color=\"green\"><b>Sticky</b></font>)";
    else
      $string .= " (sticky)";
  }

  if (isset($thread['flag']['Locked']) && !$msg['pmid']) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/lock.gif\" alt=\"locked\">";
    else
      $string .= " (locked)";
  }

  if ($msg['state'] == 'OffTopic' && isset($user->pref['SimpleHTML']))
    $string .= " (OffTopic)";

  if ($msg['state'] == 'Deleted')
    $string .= " (<font color=\"red\"><b>Deleted</b></font>)";
  else if ($msg['state'] != "Active" && $msg['state'] != "OffTopic")
    $string .= " (" . $msg['state'] . ")";

  $page = $tpl->get_var("PAGE");

  return append_tools($user, $string, $forum, $thread, $msg, $page);
}

function append_tools($user, $string, $forum, $thread, $msg, $page)
{
  $stoken = $user->token();
  switch ($msg['state']) {
  case "":
    if ($user->capable($forum['fid'], 'OffTopic'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\">am</a>";
    break;
  case "Moderated":
    if ($user->capable($forum['fid'], 'Moderate'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\" title=\"Un-moderate\">um</a>";
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Deleted&amp;mid=" . $msg['mid'] . "\" title=\"Moderate\">dm</a>";
    break;
  case "OffTopic":
    if ($user->capable($forum['fid'], 'OffTopic'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\" title=\"Mark on-topic\">uo</a>";
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Deleted&amp;mid=" . $msg['mid'] . "\" title=\"Delete\">dm</a>";
    break;
  case "Deleted":
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\" title=\"Undelete\">ud</a>";
    break;
  case "Active":
    if ($user->capable($forum['fid'], 'OffTopic'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=OffTopic&amp;mid=" . $msg['mid'] . "\" title=\"Mark offtopic\">om</a>";
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&amp;token=$stoken&amp;state=Deleted&amp;mid=" . $msg['mid'] . "\" title=\"Delete\">dm</a>";
    break;
  }

  if ($user->capable($forum['fid'], 'Lock') && !$msg['pmid']) {
    if (isset($thread['flag']['Locked']))
      $string .= " <a href=\"/" . $forum['shortname'] . "/unlock.phtml?tid=" . $msg['tid'] . "&amp;page=$page&amp;token=$stoken\" title=\"Unlock\">ul</a>";
    else
      $string .= " <a href=\"/" . $forum['shortname'] . "/lock.phtml?tid=" . $msg['tid'] . "&amp;page=$page&amp;token=$stoken\" title=\"Lock\">lt</a>";
    if (isset($thread['flag']['Sticky']))
      $string .= " <a href=\"/" . $forum['shortname'] . "/sticky.phtml?tid=" . $msg['tid'] . "&amp;stick=0&amp;page=$page&amp;token=$stoken\" title=\"Unstick thread\">us</a>";
    else
      $string .= " <a href=\"/" . $forum['shortname'] . "/sticky.phtml?tid=" . $msg['tid'] . "&amp;stick=1&amp;page=$page&amp;token=$stoken\" title=\"Sticky thread\">st</a>";
  }

  // if (!$msg['pmid']) $string .= sprintf(" %s (%d)", gen_date($user, $thread['unixtime']), $thread['unixtime']);
  return $string;
}
// vim: sw=2
?>
