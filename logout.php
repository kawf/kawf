<?php

require('forum/config.inc');
require('sql.inc');

require('account.inc');

sql_open_readwrite();

if (isset($user)) {
  $sql = "update accounts set forumcookie = '' where aid = " . $user['aid'];
  mysql_query($sql) or sql_error($sql);
}

header("Location: $page");

//header("Set-Cookie: ForumAccount=; expires=Mon, 07-Feb-2000 20:44:04 EST; path=$urlroot/; domain=.audiworld.com");
setcookie("ForumAccount", "", time() - 60, "$urlroot/", ".audiworld.com");
setcookie("ForumAccount", "", time() - 60, "$urlroot/");
setcookie("ForumAccount", "", time() - 60, "/", ".audiworld.com");
setcookie("ForumAccount", "", time() - 60, "/");

?>

You have been logged out

