<html>
<head>
<title>{DOMAIN} Forums: Tracking</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva; font-size: smaller }
td { font-family: verdana, arial, geneva; font-size: smaller }
.trow1 { background: #ccccee }
.trow2 { background: #ddddff }
.row1 { background: #eeeeee }
.row2 { background: #ffffff }
ul.thread { margin-top: 0.2em; margin-bottom: 0.3em; margin-left: 2em; padding-left: 2em; }
ul {margin-left: 1em; padding-left: 1em; }
-->
</style>
</head>
<body bgcolor="#ffffff">

{HEADER}

<center>
{AD}
</center>

<hr width="100%" size="1">

<table width="600">
<tr><td>
  <font size="-2">
  &nbsp; &nbsp;[<a href="/tips/">Reading Tips</a>] [<a href="/search/" target="_top">Search</a>] [<a href="/logout.phtml?url=/login.phtml">Logout</a>]
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

<table width="600">
<tr><td>
  <font size="-2">
  &nbsp; &nbsp;[<a href="/tips/">Reading Tips</a>] [<a href="/search/" target="_top">Search</a>] [<a href="/logout.phtml?url=/login.phtml">Logout</a>]
  </font>
</td></tr>
</table>

<br>

{FOOTER}

</body>
</html>

