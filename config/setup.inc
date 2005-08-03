<?php
/* Where the kawf tree sits in the filesystem */
$srcroot = "/home/jerdfelt/software/kawf";

/* Where all of the templates reside */
$template_dir = "/web/kawf.org/forums/dev/templates/";

/* This is optional. Don't declare the variable if you don't want it added */
#$include_append = "$srcroot/config:$srcroot/include:$srcroot/user/account";

/* Uncomment this if you have your own account management stuff */
#$dont_use_account = true;

/* Uncomment this if you want to temporarily creation of new accounts */
#$create_disabled = 1;

/* Uncomment this if you want to force people to log in to read the forums */
#$login_to_read = 1;

/* Comment this out if you want to add your own advertising stuff */
$ad_generic = 1;

/* This is used to set the DOMAIN variable in templates */
$domain = "kawf.org";

/*
 * At audiworld.com, we have different configs for production and development
 * sites, so we can have correct URL's, etc
 */
$config = "config";
?>
