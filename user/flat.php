<?php

while (list($key) = each($indexes)) {
  $sql = "select count(*) from f_messages" . $indexes[$key]['iid'];
  $result = mysql_query($sql) or sql_error($sql);

  list($nummids) = mysql_fetch_row($result);
  $curmid = 0;

  $nummessages = 0;
  while ($curmid < $nummids) {
    $sql = "select mid from f_messages" . $indexes[$key]['iid'] . " where state = 'Active' order by mid limit $nummessages,1000;";
    $result = mysql_query($sql) or sql_error($sql);

    $curmid += mysql_num_rows($result);

    while (list($mid) = mysql_fetch_row($result)) {
      $nummessages++;
?>
<a href="/<?php echo $forum['shortname'] . "/msgs/" . $mid; ?>.phtml">&nbsp;</a><br>
<?php
    }

    mysql_free_result($result);
  }
}
?>
