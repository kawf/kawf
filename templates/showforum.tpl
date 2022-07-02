<table class="forumheader">
<tr>
{FORUM_HEADER}
</tr>
</table>

<table class="tools">
<tr>
  <td class="left">
    <a href="/tips/?page={PAGE}" target="_blank">Tips</a>
  | <a href="/search/?forum={FORUM_SHORTNAME}&amp;page={PAGE}" target="_blank">Search</a>
  | <a href="#post"><b>Post New Thread</b></a>
  | <a href="#" id="view-all-images">View All Images</a>
  | <a href="#" id="night-mode">Night Mode: <span id="night-mode-status">Off</span></a>
  <div style="display: inline"> | </div>
  <a href="#" id="admin-mode">Admin Mode: <span id="admin-mode-status">Disabled</span></a>
  </td>
  <td class="right">
<!-- BEGIN tracked_threads -->
    <a href="/{FORUM_SHORTNAME}/tracking.phtml"><b>Tracked threads</b></a>
<!-- END tracked_threads -->
<!-- BEGIN update_all -->
  | <a href="/{FORUM_SHORTNAME}/markuptodate.phtml?tid=all&amp;page={PAGE}&amp;token={USER_TOKEN}&amp;time={TIME}">Update all</a>
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
<a id="post"><img src="/pics/post.gif" alt="post message"></a>
{FORM}
</div>
