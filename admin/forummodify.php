<?php

$user->req("ForumAdmin");

/* If submit is set, shove the data into the database (well, after some */
/* error checking) */
if (isset($submit)) {
  if (isset($read))
    $options[] = "Read";
  if (isset($postthread))
    $options[] = "PostThread";
  if (isset($postreply))
    $options[] = "PostReply";
  if (isset($postedit))
    $options[] = "PostEdit";
  if (isset($offtopic))
    $options[] = "OffTopic";

  if (isset($options))
    $options = implode(",", $options);
  else
    $options = "";

  sql_query("replace into f_forums " .
		"( fid, name, shortname, options ) " .
		"values " .
		"( '" . addslashes($fid) . "', " .
		" '" . addslashes($name) . "'," .
		" '" . addslashes($shortname) . "'," .
		" '" . addslashes($options) . "'" .
		")");

  Header("Location: index.phtml?message=" . urlencode("Forum Modified"));
  exit;
}  

/* If we find an ID, means that we're in update mode */
if (!isset($fid)) {
  page_header("Modify forum");
#  page_show_nav("1.2");
  ads_die("", "No forum ID specified (fid)");
}

$forum = sql_querya("select * from f_forums where fid = '" . addslashes($fid) . "'");
$options = explode(",", $forum['options']);

foreach ($options as $name => $value)
  $options[$value] = true;

page_header("Modify forum '" . $forum['name'] . "'");
#page_show_nav("1.2");
?>

<form method="post" action="<?php echo basename($PHP_SELF);?>">
<input type="hidden" name="fid" value="<?php echo $forum['fid'];?>">
<table>
 <tr>
  <td>fid:</td>
  <td><?php echo $forum['fid']; ?></td>
 </tr>
 <tr>
  <td>Long Name:</td>
  <td><input type="text" name="name" value="<?php echo $forum['name']; ?>"></td>
 </tr>
 <tr>
  <td>Short Name:</td>
  <td><input type="text" name="shortname" value="<?php echo $forum['shortname']; ?>"></td>
 </tr>
 <td>
  <td>Read Messages:</td>
  <td><input type="checkbox" name="read"<?php if (isset($options['Read'])) echo " checked"; ?>></td>
 </tr>
 <td>
  <td>Posting new threads:</td>
  <td><input type="checkbox" name="postthread"<?php if (isset($options['PostThread'])) echo " checked"; ?>></td>
 </tr>
 <td>
  <td>Posting new replies:</td>
  <td><input type="checkbox" name="postreply"<?php if (isset($options['PostReply'])) echo " checked"; ?>></td>
 </tr>
 <td>
  <td>Edit Posts:<br><small>(includes deleting)</small></td>
  <td valign="top"><input type="checkbox" name="postedit"<?php if (isset($options['PostEdit'])) echo " checked"; ?>></td>
 </tr>
 <td>
  <td>Off-Topic Posts:</td>
  <td valign="top"><input type="checkbox" name="offtopic"<?php if (isset($options['OffTopic'])) echo " checked"; ?>></td>
 </tr>
 <tr>
  <td></td>
  <td><input type="submit" name="submit" value="Update"></td>
 </tr>
</table>
</form>

<?php
page_footer();
?>
