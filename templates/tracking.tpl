<!-- BEGIN normal -->
<!-- BEGIN hr -->
<hr width="100%" size="1">
<!-- END hr -->
<table class="forumheader">
<tr>
{FORUM_HEADER}
</tr>
</table>
<br>

<table class="tools">
<tr>
 <td class="left">
    <a href="/tips/?page={PAGE}" target="_blank"><b>Forum Tips</b></a>
  | <a href="/search/?forum={FORUM_SHORTNAME}&amp;page={PAGE}" target="_blank">Search Forum</a>
  | <a href="/{FORUM_SHORTNAME}"><b>Go to {FORUM_NAME}</b></a>
  </td>
<!-- BEGIN update_all -->
<td class="right"><a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&amp;page={PAGE}&amp;token={USER_TOKEN}&amp;time={TIME}">Update all</a></td>
<!-- END update_all -->
</tr>
</table>

<table class="threads">
<!-- BEGIN row -->
<tr class="{CLASS}">
  <td>
{MESSAGES}
  </td>
  <td class="threadlinks">
{THREADLINKS}
  </td>
</tr>
<!-- END row -->
</table>
<!-- END normal -->

<!-- BEGIN simple -->
<table class="forumheader">
<tr>
{FORUM_HEADER}
</tr>
</table>
<br>

<!-- BEGIN update_all -->
<table class="tools"><tr>
<td class="right"><a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&amp;page={PAGE}&amp;token={USER_TOKEN}&amp;time={TIME}">Update all</a></td>
</tr></table>
<!-- END update_all -->

<!-- BEGIN row -->
{MESSAGES}
<!-- END row -->
<!-- END simple -->

<div class="tools">
    <a href="/tips/?page={PAGE}" target="_blank"><b>Forum Tips</b></a>
  | <a href="/search/?page={PAGE}" target="_blank">Search Forums</a>
</div>
