<?php
/* MySQL account info */
$sql_username = "root";
$sql_password = "password";
$sql_host = "localhost";
$database = "kawf";

$bounce_host = "bounce.kawf.org";

/* note that $cookie_host can also be an array to guarantee cookies are stored
 * for several domains at once: */
/* $cookie_host = array(".kawf.org",".kawf.net"); */

$cookie_host = ".kawf.org";

/* imgur client id and secret, to allow image uploading */
$imgur_client_id = "";
$imgur_client_secret = "";

/* Skip32 key for encrypting viewer AIDs */
// set here or in config-local.inc
//$viewer_aid_key = "0123456789abcdef0123";

/* allow user to override default in a separate file (prevents merge problems
   when using source control */
if (is_file(dirname(__FILE__) . "/config-local.inc") )
    include_once dirname(__FILE__) . "/config-local.inc";

/*
// Example forum navigation configuration
// Copy to config-local.inc and customize
$forum_navigation = array(
    'main' => array(
        'general' => 'General Discussion',
        'tech' => 'Technical Support',
        'photos' => 'Photo Gallery',
        'classifieds' => 'Classifieds',
    ),
    'offtopic' => array(
        'offtopic' => 'Off Topic',
    ),
);
*/

/*
// Sponsor configuration example:
// Copy to config-local.inc and customize
$config_sponsor = array(
    'text' => 'Circle of Trust',
    'url' => 'http://kawf.sourceforge.net/'
);
*/
/*
$config_hosting = array(
    'text' => 'Example, Inc.'
    'url' => 'https://example.com',
);
*/

/*
// PayPal donation button configuration example:
// Copy to config-local.inc and customize
$config_paypal = array(
    'hosted_button_id' => 'YOUR_BUTTON_ID_HERE'  // Required: Your PayPal hosted button ID
);
*/
?>
