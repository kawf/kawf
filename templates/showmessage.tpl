<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>{DOMAIN} Forums: {MSG_SUBJECT}</title>
<link rel=StyleSheet href="/css/main.css" type="text/css">
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
<tr>
  <td align="left">
  <font size="-2">
    [ <a href="/{FORUM_SHORTNAME}/threads/{MSG_TID}.phtml#{MSG_MID}">All messages</a> ]
    [ <a href="/search/?forum={FORUM_SHORTNAME}" target="_top">Search Forums</a> ]
    [ <a href="../"><b>Return to {FORUM_NAME}</b></a> ]
    </font>
  </td>
  <td align="right">
  <font size="-2">
    [ <a href="/preferences.phtml?page={PAGE}">Preferences</a> ]
  </font>
  </td>
</tr>
</table>
<br>

<table>
<tr><td>
{MESSAGE}
</td></tr>
</table>

<a name="thread"><b>Thread:</b></a><br>
<table width="100%">
<tr bgcolor="{BGCOLOR}">
<td>
{THREAD}
</td>
<td valign="top">
{THREADLINKS}
</td>
</tr>
</table>

<br>

<table>
<tr><td align="center">
<a name="post"><img src="/pics/followup.gif" alt="post follow up"></a>
</td></tr>

<tr><td>
{FORM}
</td></tr>
</table>

<table width="100%">
<tr>
  <td align="left">
  <font size="-2">
    [ <a href="/{FORUM_SHORTNAME}/threads/{MSG_TID}.phtml#{MSG_MID}">All messages</a> ]
    [ <a href="/search/?forum={FORUM_SHORTNAME}" target="_top">Search Forums</a> ]
    [ <a href="../"><b>Return to {FORUM_NAME}</b></a> ]
    </font>
  </td>
</tr>
</table>
<br>

{FOOTER}
</body>
</html>
