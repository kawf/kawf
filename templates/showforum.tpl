<html>
<head>
<title>{DOMAIN} Forums: {FORUM_NAME}</title>
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
  <td colspan="2">
  <font size="-2">
  [ <a href="/tips/?page={PAGE}"><b>Forum Tips</b></a> ]
  [ <a href="/search/?forum={FORUM_SHORTNAME}" target="_top">Search Forums</a> ]
  [ <a href="#post">Post New Thread</a> ]
  [ <a href="/preferences.phtml?page={PAGE}">Preferences</a> ]
  </font>
  </td>
</tr>
<tr>
  <td align="left" valign="bottom"><font size="-2"><b>Page:</b> {PAGES}</font></td>
  <td align="right" valign="bottom">
  <font size="-2">Total threads: {NUMTHREADS}, total pages: {NUMPAGES}</font>
  </td>
</tr>
</table>


<!-- BEGIN update_all -->
<div align="right"><a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&page={PAGE}&time={TIME}">Update all</a></div>
<!-- END update_all -->

<!-- BEGIN normal -->
<table width="100%" border="0" cellpadding="2" cellspacing="2">
<!-- BEGIN row -->
<tr class="{CLASS}">
  <td>
{MESSAGES}
  </td>
  <td valign="top">
{MESSAGELINKS}
  </td>
</tr>
<!-- END row -->
</table>
<!-- END normal -->

<!-- BEGIN simple -->
<!-- BEGIN row -->
{MESSAGES}
<!-- END row -->
<!-- END simple -->

<table width="100%">
<tr>
  <td align="left" valign="bottom"><font size="-2"><b>Page:</b> {PAGES}</font></td>
  <td align="right" valign="bottom">
  <font size="-2">Total threads: {NUMTHREADS}, total pages: {NUMPAGES}</font>
  </td>
</tr>
</table>

<table>
<tr><td align="center">
<a name="post"><img src="/pics/post.gif" alt="post message"></a>
</td></tr>

<tr><td>
{FORM}
</td></tr>
</table><br>

<!-- b>{ACTIVE_USERS}</b> users and <b>{ACTIVE_GUESTS}</b> guests have been browsing the forums in the last 15 minutes<p -->

{FOOTER}

</body>
</html>

