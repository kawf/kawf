<?php

require_once("forumuser.inc.php");

/* This is the AdminUser class */
class AdminUser extends AccountUser {
    function valid()
    {
	if (!isset($this->aid)) return false;
	return $this->admin();
    }
}

?>
