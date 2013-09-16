<?php
require_once("page-yatt.inc.php");

$dir = new YATT();
$dir->load("$template_dir/directory.yatt");

$sth = db_query("select fid,name,shortname from f_forums where options like '%Searchable%' order by name");

for ($i=0; $row = $sth->fetch(); $i++) {
    $dir->set("r", $i & 1);
    $fid = $row['fid'];
    /* should only count active and off-topic, but its too slow */
    try {
      $row2 = db_query_first("select count(*) from f_messages$fid");
      $count = $row2 ? $row2[0] : NULL;
    } catch(PDOException $e) {
      $count = NULL;
    }
    $dir->set("count", $count);
    $dir->set("row", $row);
    $dir->parse("dir.row");
}
$sth->closeCursor();
$dir->parse("dir");

print generate_page('Directory', $dir->output());
?>
