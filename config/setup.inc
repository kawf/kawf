<?php
/* Where the kawf tree sits in the filesystem */
$srcroot = dirname(__FILE__) . "/..";

/* Where all of the templates reside */
/* easy install, same location as srcroot */
$template_dir = "$srcroot/templates";
/* templates in different directory from source */
#$template_dir = "/var/www/vhosts/kawf.org/forums/templates/";

/* This is optional. Don't declare the variable if you don't want it added */
#$include_append = "$srcroot/lib:$srcroot/config:$srcroot/include:$srcroot/user/account";

/* Uncomment this if you have your own account management stuff */
#$dont_use_account = true;

/* Set this to true if you want to disable creation of new accounts. */
$create_disabled = false;

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

/* where your stylesheets sit */
$css_dir = "$srcroot/config/css";

/* path to prepend to css href */
$css_href_dir = "/css";

/* set to 1 for debugging */
$Debug = 0;

/* User token expiration in 15 minute increments: 3 means 45 minutes. */
$user_token_expiration = 7;

/* Enable global messages */
$enable_global_messages = false;

/* allow user to override default in a separate file (prevents merge problems
   when using source control */
if (is_file(dirname(__FILE__) . "/setup-local.inc") )
    include_once dirname(__FILE__) . "/setup-local.inc";
?>
