<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>{DOMAIN} Forums: {MSG_SUBJECT}</title>
<link rel=StyleSheet href="{CSS_HREF}" type="text/css">
</head>

<body bgcolor="#ffffff">

{HEADER}

<center>
{AD}
</center>

<hr width="100%" size="1">

<table width="100%">
<tr>
{FORUM_HEADER}
</tr>
</table>

<table width="100%">
<tr class="tools">
  <td align="left">
    <a href="/{FORUM_SHORTNAME}/threads/{MSG_TID}.phtml#{MSG_MID}">All messages</a>
  | <a href="/search/?forum={FORUM_SHORTNAME}&amp;page={PAGE}" target="_blank">Search Forums</a>
  | <a href="../"><b>Return to {FORUM_NAME}</b></a>
  </td>
  <td align="right">
    <a href="/preferences.phtml?page={PAGE}">Preferences</a>
  </td>
</tr>
</table>

<div class="showmessage">
{MESSAGE}
<a name="thread"><b>Thread:</b></a><br>
<table width="100%">
<tr bgcolor="{BGCOLOR}">
<td>
{THREAD}
</td>
<td class="threadlinks" valign="top">
{THREADLINKS}
</td>
</tr>
</table>
<a name="post"><img src="/pics/followup.gif" alt="post follow up"></a>
{FORM}
</div>

<table width="100%">
<tr class="tools">
  <td align="left">
    <a href="/{FORUM_SHORTNAME}/threads/{MSG_TID}.phtml#{MSG_MID}">All messages</a>
  | <a href="/search/?forum={FORUM_SHORTNAME}&amp;page={PAGE}" target="_blank">Search Forums</a>
  | <a href="../"><b>Return to {FORUM_NAME}</b></a>
  </td>
</tr>
</table>
<br>

{FOOTER}
</body>
</html>
