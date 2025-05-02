<?php

require_once("error.inc.php");

/* We use the templates for everything */
// require_once("template.inc"); // REMOVED - No longer used here

function safe_server_var($key)
{
    return (isset($_SERVER[$key])?$_SERVER[$key]:'');
}

$server_name=safe_server_var('SERVER_NAME');
$server_port=safe_server_var('SERVER_PORT');

$remote_addr=safe_server_var('REMOTE_ADDR');

$http_host=safe_server_var('HTTP_HOST');
$request_uri=safe_server_var('REQUEST_URI');
$request_path=(@parse_url($request_uri))['path'];
$script_name=safe_server_var('SCRIPT_NAME');
$path_info=safe_server_var('PATH_INFO');

$query_string=safe_server_var('QUERY_STRING');

function url_origin( $s, $use_forwarded_host = false )
{
    $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
    $sp       = strtolower( $s['SERVER_PROTOCOL'] );
    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
    $port     = $s['SERVER_PORT'];
    $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
    $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
    $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}

function full_url( $s, $use_forwarded_host = false )
{
    return url_origin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
}

/**
 * Get the base URL for the application, including protocol and domain
 * This is used for constructing absolute URLs and preventing open redirects
 *
 * @param bool $use_forwarded_host Whether to use X-Forwarded-Host header
 * @return string The base URL (e.g. "https://forums.example.com")
 */
function get_base_url($use_forwarded_host = false)
{
    global $server_name;

    // Use url_origin() to get protocol and host
    $base = url_origin($_SERVER, $use_forwarded_host);

    // If we're in a subdirectory, add it to the base URL
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    if ($script_dir !== '/' && $script_dir !== '\\') {
        $base .= $script_dir;
    }

    return $base;
}

function getmicrotime()
{
  $mtime = explode(" ", microtime());
  return intval($mtime[0] * 1000000);
}

/* Seed the random number generator */
mt_srand(getmicrotime());

function css_href($filename="main.css")
{
    global $css_dir, $css_href_dir;

    if (!file_exists("$css_dir/$filename"))
	return null;

    $time = filemtime("$css_dir/$filename");
    return "$css_href_dir/$filename?$time";
}

function skin_css_href()
{
    global $default_skin;
    return css_href("$default_skin.css");
}

function browser_css_href()
{
    require_once("phpSniff.class.php");
    $ua = new phpSniff();
    return css_href("browser-".$ua->property('browser').".css");
}

function js_href($filename="main.js", $cache_buster=true)
{
    global $js_dir, $js_href_dir;

    if (!file_Exists("$js_dir/$filename"))
    return null;

    if ($cache_buster) {
        $time = filemtime("$js_dir/$filename");
        return "$js_href_dir/$filename?$time";
    } else
        return "$js_href_dir/$filename";
}

function time_ago($since_secs)
{
	$ago = time() - $since_secs;

	$years = floor($ago / 60 / 60 / 24 / 365);
	$ago -= ($years * 60 * 60 * 24 * 365);

	$months = floor($ago / 60 / 60 / 24 / 30.4);
	$ago -= ($months * 60 * 60 * 24 * 30.4);

	$weeks = floor($ago / 60 / 60 / 24 / 7);
	$ago -= ($weeks * 60 * 60 * 24 / 7);

	$days = floor($ago / 60 / 60 / 24);
	$ago -= ($days * 60 * 60 * 24);

	$hours = floor($ago / 60 / 60);
	$ago -= ($hours * 60 * 60);

	$mins = floor($ago / 60);
	$ago -= ($mins * 60);

	if ($years)
		return $years . " year" . ($years == 1 ? "" : "s") . " ago";
	if ($months)
		return $months . " month" . ($months == 1 ? "" : "s") . " ago";
	if ($weeks)
		return $weeks . " week" . ($weeks == 1 ? "" : "s") . " ago";
	if ($days)
		return $days . " day" . ($days == 1 ? "" : "s") . " ago";
	if ($hours)
		return $hours . " hour" . ($hours == 1 ? "" : "s") . " ago";
	if ($mins)
		return $mins . " minute" . ($mins == 1 ? "" : "s") . " ago";
	if ($ago <= 1)
		return "just now";

	return $ago . " second" . ($ago == 1 ? "" : "s") . " ago";
}

function get_forum_navigation()
{
    global $forum_navigation;

    if (!isset($forum_navigation)) {
        // Generate from DB if not specified
        $nav = array('main' => array(), 'offtopic' => array());
        $sth = db_query("SELECT shortname, name, options FROM f_forums ORDER BY name");
        while ($row = $sth->fetch()) {
            $options = explode(',', $row['options']);
            // The logic here may be counterintuitive. The "offtopic" flag means "support marking threads as offtopic".
            // This means that forums that are "offtopic" focussed do NOT allow this, thus making them offtopic.
            $name = $row['name'];
            if (in_array('OffTopic', $options)) {
                $nav['main'][$row['shortname']] = $name;
            } else {
                $nav['offtopic'][$row['shortname']] = $name;
            }
        }
        return $nav;
    }

    // If $forum_navigation is set in config, we need to fetch the full names from DB
    $sth = db_query("SELECT shortname, name FROM f_forums");
    $forum_names = array();
    while ($row = $sth->fetch()) {
        $forum_names[$row['shortname']] = $row['name'];
    }

    // Create a new navigation array with the correct structure
    $nav = array('main' => array(), 'offtopic' => array());
    foreach ($forum_navigation as $category => $shortnames) {
        foreach ($shortnames as $index => $shortname) {
            if (isset($forum_names[$shortname])) {
                $nav[$category][$shortname] = $forum_names[$shortname];
            }
        }
    }
    return $nav;
}

function new_yatt($template, $forum = null) {
    global $template_dir;
    $yatt = new YATT($template_dir, $template);
    if ($forum) {
        $yatt->set('FORUM_NAME', htmlspecialchars($forum['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $yatt->set('FORUM_SHORTNAME', htmlspecialchars($forum['shortname'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $yatt->set('FORUM_NOTICES', isset($forum['notices']) ? $forum['notices'] : '');
    }
    return $yatt;
}

/**
 * Get the current page context from request parameters and format it as a URL parameter
 * @return string The formatted URL parameter string (e.g. "page=value")
 */
function format_page_param() {
    $context = get_page_context();
    return $context ? "page=" . urlencode($context) : '';
}

/**
 * Get the current page context from request parameters without the page= prefix
 * @param bool $use_fallback Whether to fall back to current URL if no page parameter exists
 * @return string The page context value, URL encoded for safe use in forms and URLs
 */
function get_page_context($use_fallback = true) {
    global $script_name, $path_info;
    return $_REQUEST['page'] ?? ($use_fallback ? ($script_name . $path_info) : '');
}
?>
