<?php

$user->req("ForumAdmin");

include_once("user/tables.inc");

/* If submit is set, shove the data into the database (well, after some */
/* error checking) */
if (isset($_POST['submit'])) {
  if (isset($_POST['read']))
    $options[] = "Read";
  if (isset($_POST['postthread']))
    $options[] = "PostThread";
  if (isset($_POST['postreply']))
    $options[] = "PostReply";
  if (isset($_POST['postedit']))
    $options[] = "PostEdit";
  if (isset($_POST['offtopic']))
    $options[] = "OffTopic";
  if (isset($_POST['searchable']))
    $options[] = "Searchable";

  if (isset($options))
    $options = implode(",", $options);
  else
    $options = "";

  db_exec("insert into f_forums " .
		"( name, shortname, options ) " .
		"values ( ?, ?, ? )",
		array($_POST['name'], $_POST['shortname'], $options));
  $fid = db_last_insert_id();

  db_exec("insert into f_indexes ( fid, minmid, maxmid, mintid, maxtid, active, moderated, deleted ) values ( ?, 1, 0, 1, 0, 0, 0, 0 )", array($fid));
  $iid = db_last_insert_id();

  db_exec("insert into f_unique ( fid, type, id ) values ( ?, 'Message', 0 )", array($fid));
  db_exec("insert into f_unique ( fid, type, id ) values ( ?, 'Thread', 0 )", array($fid));

  db_exec(sprintf($create_message_table, $iid));
  db_exec(sprintf($create_thread_table, $iid));

  Header("Location: index.phtml?message=" . urlencode("Forum Added"));
  exit;
}  

page_header("Add Forum");
#page_show_nav("1.2");
?>

<form method="post" action="<?php echo basename($_SERVER['PHP_SELF']);?>">
<table>
 <tr>
  <td>Long Name:</td>
  <td><input type="text" name="name" value=""></td>
 </tr>
 <tr>
  <td>Short Name:</td>
  <td><input type="text" name="shortname" value=""></td>
 </tr>
 <td>
  <td>Read Messages:</td>
  <td><input type="checkbox" name="read"></td>
 </tr>
 <td>
  <td>Posting new threads:</td>
  <td><input type="checkbox" name="postthread"></td>
 </tr>
 <td>
  <td>Posting new replies:</td>
  <td><input type="checkbox" name="postreply"></td>
 </tr>
 <td>
  <td>Edit Posts:<br><small>(includes deleting)</small></td>
  <td valign="top"><input type="checkbox" name="postedit"></td>
 </tr>
 <td>
  <td>Off-Topic Posts:</td>
  <td valign="top"><input type="checkbox" name="offtopic"></td>
 </tr>
 <td>
  <td>Searchable:</td>
  <td valign="top"><input type="checkbox" name="searchable"></td>
 </tr>
 <tr>
  <td></td>
  <td><input type="submit" name="submit" value="Add"></td>
 </tr>
</table>
</form>

<?php
page_footer();
?>
