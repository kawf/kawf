<html>
<head>
<title>{DOMAIN} Forums: {FORUM_NAME}</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva; font-size: smaller }
td { font-family: verdana, arial, geneva; font-size: smaller }
.trow0 { background: #ddddff }
.trow1 { background: #ccccee }
.drow0 { background: #ddffdd }
.drow1 { background: #cceecc }
.mrow0 { background: #ffdddd }
.mrow1 { background: #eecccc }
.row0 { background: #ffffff }
.row1 { background: #eeeeee }
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

<table width="100%">
<tr>
{FORUM_HEADER}
</tr>
</table>

<table width="600">
<tr><td>
  <font size="-2">
  <b>Page:</b> {PAGES}

  &nbsp; &nbsp;
  [<a href="/tips/">Reading Tips</a>]
  [<a href="/search/" target="_top">Search</a>]
  [<a href="#post">Post New Thread</a>]

  </font>
</td></tr>
</table>

Total threads: {NUMTHREADS}, total pages: {NUMPAGES}<br>

<!-- BEGIN update_all -->
<div align="right"><a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&page={PAGE}">Update all</a></div>
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

<table width="600">
<tr><td>
  <font size="-2">
  <b>Page:</b> {PAGES}

  &nbsp; &nbsp;
  [<a href="/tips/">Reading Tips</a>]
  [<a href="/search/" target="_top">Search</a>]
  [<a href="#post">Post New Thread</a>]

  </font>
</td></tr>
</table>

<table width="600">
<tr><td align="center">
<a name="post">
<img src="/pics/post.gif">
</td></tr>

<tr><td>
{FORM}
</td></tr>
</table><br>

<!-- b>{ACTIVE_USERS}</b> users and <b>{ACTIVE_GUESTS}</b> guests have been browsing the forums in the last 15 minutes<p -->

{FOOTER}

</body>
</html>

