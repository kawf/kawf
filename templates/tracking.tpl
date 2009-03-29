<html>
<head>
<title>{DOMAIN} Forums: Tracking</title>
<link rel=StyleSheet href="/css/main.css" type="text/css" media="screen">
</head>
<body bgcolor="#ffffff">

{HEADER}

<center>
{AD}
</center>

<hr width="100%" size="1">

<table>
<tr><td>
  <font size="-2">
  [ <a href="/tips/?page={PAGE}"><b>Forum Tips</b></a> ]
  [ <a href="/search/" target="_top">Search Forums</a> ]
  [ <a href="/logout.phtml?url=/login.phtml&token={token}">Logout</a> ]
  [ <a href="/preferences.phtml?page={PAGE}">Preferences</a> ]
  </font>
</td></tr>
</table>

<!-- BEGIN normal -->
<table width="100%">
<tr>
{FORUM_HEADER}
</tr>
</table>
<br>

<!-- BEGIN update_all -->
<div align="right"><a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&page={PAGE}&time={TIME}">Update all</a></div>
<!-- END update_all -->

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
<table width="100%">
<tr>
{FORUM_HEADER}
</tr>
</table>
<br>

<!-- BEGIN update_all -->
<div align="right"><a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&page={PAGE}&time={TIME}">Update all</a></div>
<!-- END update_all -->

<!-- BEGIN row -->
{MESSAGES}
<!-- END row -->
<!-- END simple -->

<table>
<tr><td>
  <font size="-2">
  [ <a href="/tips/?page={PAGE}"><b>Forum Tips</b></a> ]
  [ <a href="/search/" target="_top">Search Forums</a> ]
  [ <a href="/logout.phtml?url=/login.phtml&token={token}">Logout</a> ]
  [ <a href="/preferences.phtml?page={PAGE}">Preferences</a> ]
  </font>
</td></tr>
</table>

<br>

{FOOTER}

</body>
</html>

