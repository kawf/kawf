<?php
/* Where the kawf tree sits in the filesystem */
$srcroot = "/home/jerdfelt/software";

/* Where all of the templates reside */
$template_dir = "/web/kawf.org/forums/dev/templates/";

/* This is optional. Don't declare the variable if you don't want it added */
$include_append = "/home/jerdfelt/software/php";

/* Style for guests and initial for new users */
$default_style = "classic";

/*
 * At audiworld.com, we have different configs for production and development
 * sites, so we can have correct URL's, etc
 */
$config = "config";

include("$srcroot/kawf/user/main.php");
?>
