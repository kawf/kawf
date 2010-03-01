%begin [debug]
<pre>%[debug]</pre>
%end [debug]
%begin [form]
<form method="post" action="gmessage.phtml">
<input type="hidden" name="gid" value="%[msg(gid)]">
<input type="hidden" name="token" value="%[token]">
<table>
    <tr>
	<td>Slot %[msg(gid)]</td>
	<td>Subject: <input type="text" name="subject" value="%[msg(subject)]"></td>
	<td>URL: <input type="text" name="url" value="%[msg(url)]"></td>
	<td><input type="submit" name="submit" value="Update"></td>
    </tr>
</table>
</form>
%end [form]
%begin [table]
    <table class="contents">
	<tr><th>GID</th><th>Subject</th><th>URL</th><th>Name</th><th>Date</th><th>Status</th><th>Hidden by</th></tr>
%begin [row]
	<tr class="row%[r]">
	    <td><a href="gmessage.phtml?gid=%[msg(gid)]&amp;%[gid(args)]" title="Edit GID %[msg(gid)]">Slot %[msg(gid)]</a></td>
	    <td>%[msg(subject)]</td>
	    <td><a href="%[msg(url)]" target="_blank" title="Go here">%[msg(url)]</a></td>
	    <td><a href="gmessage.phtml?token=%[token]&amp;gid=%[msg(gid)]&amp;%[name(args)]" title="%[name(title)]">%[msg(name)]</a></td>
	    <td><a href="gmessage.phtml?token=%[token]&amp;gid=%[msg(gid)]&amp;%[date(args)]" title="%[date(title)]">%[msg(date)]</a></td>
	    <td><a href="%[state(url)]?token=%[token]&amp;gid=%[msg(gid)]&amp;%[state(args)]" title="%[state(title)]">%[msg(state)]</a></td>
%begin [unhide]
	    <td><a href="gmessage.phtml?token=%[token]&amp;gid=%[msg(gid)]&amp;unhide" title="Unhide from all users">%[hidden]</a> users</td>
%end [unhide]
%begin [nounhide]
	    <td>%[hidden] users</td>
%end [nounhide]
	</tr>
%end [row]
    </table>
%end [table]
