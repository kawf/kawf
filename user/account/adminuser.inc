<?php

require_once("forumuser.inc");

/* This is the AdminUser class */
class AdminUser extends AccountUser {
    function valid()
    {
	if($this->aid!=1) return false;
	return parent::valid();
    }
}

?>
