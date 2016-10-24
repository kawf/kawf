<?php

require_once("forumuser.inc");

/* This is the AdminUser class */
class AdminUser extends AccountUser {
    function valid()
    {
	if (!isset($this->aid)) return false;
	return $this->admin();
    }
}

?>
