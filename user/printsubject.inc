<?php
require_once("message.inc");

function print_subject($thread, $msg, $replies = -1, $collapse = false)
{
  global $vmid, $user, $tthreads_by_tid, $forum, $debug_f_tracking;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    foreach ($flagexp as $flag)
      $flags[$flag] = true;
  }

  // $msg['subject']=wordwrap($msg['subject'],40,'<wbr>',1);

  $nt = "";
  if (isset($flags['NewStyle']) && isset($flags['NoText']) && count($flags)==2)
    $nt = " class=\"nt\"";

  $string = "";
  if (isset($vmid) && $vmid == $msg['mid'])
    $string .= "<span class=\"vmid\">" . $msg['subject'] . "</span>";
  else {
    if (isset($user->pref['FlatThread']))
      $string .= "<a href=\"/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\"" . $nt . ">" . $msg['subject'] . "</a>";
    else
      $string .= "<a href=\"/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\"" . $nt . ">" . $msg['subject'] . "</a>";
  }

  if(is_msg_bumped($msg))
    $string = '<em>'.$string.'</em>';

  if (isset($flags['NoText'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img class=\"flag\" src=\"/pics/nt.gif\" alt=\"no text\"" . $nt . ">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img class=\"flag\" src=\"/pics/pic.gif\" alt=\"contains picture\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Video'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img class=\"flag\" src=\"/pics/video.gif\" alt=\"contains video\">";
    else
      $string .= " (vid)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img class=\"flag\" src=\"/pics/url.gif\" alt=\"contains url\">";
    else
      $string .= " (link)";
  }

  if ($msg['state'] == 'OffTopic' && !isset($user->pref['SimpleHTML']))
    $string .= " <img class=\"flag\" src=\"/pics/ot.gif\" alt=\"off topic\">";

  $string .= "&nbsp;&nbsp;-&nbsp;&nbsp;";
  $string .= "<span class=\"username\">" . $msg['name'] . "</span>&nbsp;&nbsp;";
  $string .= "<span class=\"threadinfo\">";

  if (isset($user->pref['RelativeTimestamps']))
    $string .= "<i title=\"" . $msg['date'] . "\">" . time_ago($msg['unixtime']) . "</i>";
  else
    $string .= "<i>" . $msg['date'] . "</i>";

  if ($replies > 0)
    $string .= " ($replies " . ($replies == 1 ? "reply" : "replies") . ($collapse?" hidden":"") . ")";

  if ($msg['views']>0)
    $string .= " (" . $msg['views'] . " view" . ($msg['views'] == 1 ? "" : "s") . ")";

  $string .= "</span>\n";

  if (isset($thread['flag']['Sticky']) && !$msg['pmid']) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " (<span class=\"green\"><b>Sticky</b></span>)";
    else
      $string .= " (sticky)";
  }

  if (isset($thread['flag']['Locked']) && !$msg['pmid']) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img class=\"flag\" src=\"/pics/lock.gif\" alt=\"locked\">";
    else
      $string .= " (locked)";
  }

  if ($msg['state'] == 'OffTopic' && isset($user->pref['SimpleHTML']))
    $string .= " (OffTopic)";

  if ($msg['state'] == 'Deleted')
    $string .= " (<span class=\"red\"><b>Deleted</b></span>)";
  else if ($msg['state'] != "Active" && $msg['state'] != "OffTopic")
    $string .= " (" . $msg['state'] . ")";

  // Add timestamp to subject when debug tracking is enabled
  if ($debug_f_tracking) {
    $string .= ": " . gen_date($user, $msg['unixtime']);
  }

  return append_tools($user, $string, $forum, $thread, $msg);
}

function append_tools($user, $string, $forum, $thread, $msg)
{
  $stoken = $user->token();
  $page = format_page_param();

  switch ($msg['state']) {
  case "":
    if ($user->capable($forum['fid'], 'OffTopic'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\">am</a>";
    break;
  case "Moderated":
    if ($user->capable($forum['fid'], 'Moderate'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\" title=\"Un-moderate\">um</a>";
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?$page&amp;token=$stoken&amp;state=Deleted&amp;mid=" . $msg['mid'] . "\" title=\"Moderate\">dm</a>";
    break;
  case "OffTopic":
    if ($user->capable($forum['fid'], 'OffTopic'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\" title=\"Mark on-topic\">uo</a>";
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?$page&amp;token=$stoken&amp;state=Deleted&amp;mid=" . $msg['mid'] . "\" title=\"Delete\">dm</a>";
    break;
  case "Deleted":
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?$page&amp;token=$stoken&amp;state=Active&amp;mid=" . $msg['mid'] . "\" title=\"Undelete\">ud</a>";
    break;
  case "Active":
    if ($user->capable($forum['fid'], 'OffTopic'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?$page&amp;token=$stoken&amp;state=OffTopic&amp;mid=" . $msg['mid'] . "\" title=\"Mark offtopic\">om</a>";
    if ($user->capable($forum['fid'], 'Delete'))
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?$page&amp;token=$stoken&amp;state=Deleted&amp;mid=" . $msg['mid'] . "\" title=\"Delete\">dm</a>";
    break;
  }

  if ($user->capable($forum['fid'], 'Lock') && !$msg['pmid']) {
    if (isset($thread['flag']['Locked']))
      $string .= " <a href=\"/" . $forum['shortname'] . "/unlock.phtml?tid=" . $msg['tid'] . "&amp;$page&amp;token=$stoken\" title=\"Unlock\">ul</a>";
    else
      $string .= " <a href=\"/" . $forum['shortname'] . "/lock.phtml?tid=" . $msg['tid'] . "&amp;$page&amp;token=$stoken\" title=\"Lock\">lt</a>";
    if (isset($thread['flag']['Sticky']))
      $string .= " <a href=\"/" . $forum['shortname'] . "/sticky.phtml?tid=" . $msg['tid'] . "&amp;stick=0&amp;$page&amp;token=$stoken\" title=\"Unstick thread\">us</a>";
    else
      $string .= " <a href=\"/" . $forum['shortname'] . "/sticky.phtml?tid=" . $msg['tid'] . "&amp;stick=1&amp;$page&amp;token=$stoken\" title=\"Sticky thread\">st</a>";
  }

  // if (!$msg['pmid']) $string .= sprintf(" %s (%d)", gen_date($user, $thread['unixtime']), $thread['unixtime']);
  return $string;
}
// vim: sw=2
?>
