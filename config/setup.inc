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

/* where your javascripts sit */
$js_dir = "$srcroot/config/scripts";

/* path to prepend to javascript href */
$js_href_dir = "/scripts";

/*
 * Add a robots meta tag.  Forums with ExternallySearchable option will get
 * <meta name="robots" content="follow,index" />
 * and forums that are not ExternallySearchable will get
 * <meta name="robots" content="noindex" />
 */
$robots_meta_tag = false;

/* set to 1 for debugging */
$Debug = 0;

/* User token expiration in 15 minute increments: 3 means 45 minutes. */
$user_token_expiration = 7;

/* Enable global messages */
$enable_global_messages = true;

/* Default page */
/* $default_page = "/tracking.phtml"; */ /* old behavior */
$default_page = "/directory.phtml";

/* Workaround Debian bug 571762 */
/* http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=571762 */
/* ONLY uncomment this if you know what you are doing. */
//date_default_timezone_set(@date_default_timezone_get());

/* imgur client id and secret, to allow image uploading */
$imgur_client_id = "";
$imgur_client_secret = "";

/* allow user to override default in a separate file (prevents merge problems
   when using source control */
if (is_file(dirname(__FILE__) . "/setup-local.inc") )
    include_once dirname(__FILE__) . "/setup-local.inc";
?>
