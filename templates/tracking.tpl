<html>
<head>
<title>kawf.org Forums: Tracking</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva }
.trow1 { background: #ccccee }
.trow2 { background: #ddddff }
.row1 { background: #eeeeee }
.row2 { background: #ffffff }
ul.thread { margin-top: 0.2em; margin-bottom: 0.3em }
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
  <font face="Verdana, Arial, Geneva" size="-2">
  &nbsp; &nbsp;[<a href="/forum/tips.shtml">Reading Tips</a>] [<a href="/search/" target="_top">Search</a>]
  </font>
</td></tr>
</table>

<font face="Verdana, Arial, Geneva" size="-1">

<!-- BEGIN normal -->
<table width="100%">
<tr>
{FORUM_HEADER}
</tr>
</table>
<br>

<!-- BEGIN update_all -->
<div align="right"><a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&page={PAGE}">Update all</a></div>
<!-- END update_all -->

<table width="100%" border="0" cellpadding="2" cellspacing="2">
<!-- BEGIN row -->
<tr class="{CLASS}">
  <td><font face="Verdana, Arial, Geneva" size="-1">
{MESSAGES}
  </font></td>
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
<div align="right"><a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&page={PAGE}">Update all</a></div>
<!-- END update_all -->

<!-- BEGIN row -->
{MESSAGES}
<!-- END row -->
<!-- END simple -->

</font>

<table width="600">
<tr><td>
  <font face="Verdana, Arial, Geneva" size="-2">
  &nbsp; &nbsp;[<a href="/forum/tips.shtml">Reading Tips</a>] [<a href="/search/" target="_top">Search</a>]
  </font>
</td></tr>
</table>

<br>

{FOOTER}

</body>
</html>

