<?
/* Avoid reincluding the code */
if (!isset($sqldeclared)) {
$sqldeclared=1;

/* Open's up the DB with the read/write user */
function sql_open_readwrite($db = "") {
  mysql_pconnect("localhost", "news-readwrite", "password");
  if (empty($db))
    mysql_select_db("a4");
  else
    mysql_select_db($db);
}

/* Open's up the DB with the read user */
function sql_open_readonly($db = "") {
  mysql_pconnect("localhost", "news-readonly", "password");
  if (empty($db))
    mysql_select_db("a4");
  else
    mysql_select_db($db);
}

/* Useful function to print out a standard error message */
function sql_warn($sql) {
  echo "<p>Error with SQL Query<br>\n";
  echo "<pre>$sql</pre>\n";
  echo "Error #", mysql_errno(), ": ", mysql_error(), "<br>\n";
}

function sql_error($sql, $noexit = 0) {
  sql_warn($sql);
  if (!$noexit)
    exit;
}

}
?>
