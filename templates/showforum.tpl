<table width="100%">
<tr>
{FORUM_HEADER}
</tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr class="tools">
  <td align="left">
  <a href="/tips/?page={PAGE}" target="_blank">Forum Tips</a>
| <a href="/search/?forum={FORUM_SHORTNAME}&amp;page={PAGE}" target="_blank">Search Forums</a>
| <a href="#post"><b>Post New Thread</b></a>
  </td>
  <td align="right">
<!-- BEGIN update_all -->
    <a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&amp;page={PAGE}&amp;token={USER_TOKEN}&amp;time={TIME}">Update all</a>
<!-- END update_all -->
  </td>
</tr>
<tr class="tools">
  <td align="left" valign="bottom"><b>Page:</b> {PAGES}</td>
  <td align="right" valign="bottom">{NUMTHREADS} threads in {NUMPAGES} pages</td>
</tr>
</table>


<!-- BEGIN normal -->
<table width="100%" border="0" cellpadding="2" cellspacing="2">
<!-- BEGIN row -->
<tr class="{CLASS}">
  <td>
{MESSAGES}
  </td>
  <td class="messagelinks" valign="top">
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
<tr class="tools">
  <td align="left" valign="bottom"><b>Page:</b> {PAGES}</td>
  <td align="right" valign="bottom">{NUMTHREADS} threads in {NUMPAGES} pages</td>
</tr>
</table>
<div class="forumpost">
<a name="post"><img src="/pics/post.gif" alt="post message"></a>
{FORM}
</div>
