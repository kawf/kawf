<?php
/* Where the kawf tree sits in the filesystem */
$srcroot = "/home/jerdfelt/software";

/* Where all of the templates reside */
$template_dir = "/web/kawf.org/forums/dev/templates/";

/* This is optional. Don't declare the variable if you don't want it added */
$include_append = "$srcroot/config:$srcroot/kawf/include:$srcroot/kawf/user/account";

/* Uncomment this if you have your own account management stuff */
#$dont_use_account = true;

/*
 * At audiworld.com, we have different configs for production and development
 * sites, so we can have correct URL's, etc
 */
$config = "config";

include("$srcroot/kawf/user/main.php");
?>
