<?php
require_once("page-yatt.inc.php");

$dir = new YATT();
$dir->load("$template_dir/directory.yatt");

$res = mysql_query("select fid,name,shortname from f_forums where options like '%Searchable%' order by name") or sql_error();

for ($i=0; $row = mysql_fetch_assoc($res); $i++) {
    $dir->set("r", $i & 1);
    $fid = $row['fid'];
    /* should only count active and off-topic, but its too slow */
    $count = sql_query1("select count(*) from f_messages$fid");
    $dir->set("count", $count);
    $dir->set("row", $row);
    $dir->parse("dir.row");
}
$dir->parse("dir");

echo generate_page('Directory', $dir->output());
?>
