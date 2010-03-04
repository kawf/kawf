<table class="forumheader">
<tr>
{FORUM_HEADER}
</tr>
</table>

<table class="tools">
<tr>
  <td class="left">
  <a href="/tips/?page={PAGE}" target="_blank">Forum Tips</a>
| <a href="/search/?forum={FORUM_SHORTNAME}&amp;page={PAGE}" target="_blank">Search Forums</a>
| <a href="#post"><b>Post New Thread</b></a>
  </td>
  <td class="right">
<!-- BEGIN update_all -->
    <a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&amp;page={PAGE}&amp;token={USER_TOKEN}&amp;time={TIME}">Update all</a>
<!-- END update_all -->
  </td>
</tr>
<tr class="bottom">
  <td class="left"><b>Page:</b> {PAGES}</td>
  <td class="right">{NUMTHREADS} threads in {NUMPAGES} pages</td>
</tr>
</table>


<!-- BEGIN normal -->
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
<!-- BEGIN row -->
{MESSAGES}
<!-- END row -->
<!-- END simple -->

<table class="tools">
<tr>
  <td class="left"><b>Page:</b> {PAGES}</td>
  <td class="right">{NUMTHREADS} threads in {NUMPAGES} pages</td>
</tr>
</table>
<div class="forumpost">
<a name="post"><img src="/pics/post.gif" alt="post message"></a>
{FORM}
</div>
