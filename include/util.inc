<?php

error_reporting(E_ALL & ~E_NOTICE);
/* We use the templates for everything */
require_once("template.inc");

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

function dump_env()
{
    global $server_name, $server_port, $remote_addr;
    global $http_host;
    global $request_uri, $request_path;
    global $script_name, $path_info, $query_string;

    $ret="url = '".full_url($_SERVER)."'\n";
    $ret.="server_name = '$server_name'\n";
    $ret.="server_port = '$server_port'\n";
    $ret.="remote_addr = '$remote_addr'\n";
    $ret.="http_host = '$http_host'\n";
    $ret.="request_uri = '$request_uri'\n";
    $ret.="request_path = '$request_path'\n";
    $ret.="script_name = '$script_name'\n";
    $ret.="path_info = '$path_info'\n";
    $ret.="query_string = '$query_string'\n";
    return $ret;
}

function err_not_found($description = "")
{
  global $_SERVER;
  global $template_dir, $srcroot;
  global $server_name, $server_port;
  global $request_uri, $request_path;
  global $script_name, $path_info;

  Header("HTTP/1.0 404 Not found");

  /* dump_env(); */

  if (!isset($template_dir))
    $tpl = new Template($srcroot . "/php/templates", "comment");
  else
    $tpl = new Template($template_dir, "comment");

  $tpl->set_file("errnotfound", "404.tpl");

  $tpl->set_var(array(
    "DESCRIPTION" => $description . "\n". dump_env(),
    "URI" => htmlspecialchars($request_uri, ENT_QUOTES),
    "SERVER_SOFTWARE" => $_SERVER['SERVER_SOFTWARE'],
    "SERVER_NAME" => $server_name,
    "SERVER_PORT" => $server_port,
  ));

  $tpl->pparse("content", "errnotfound");

  //phpinfo(INFO_VARIABLES);
  exit;
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

function debug_log($msg)
{
    global $DEBUG_LOG;
    $DEBUG_LOG .= $msg;
}

function get_debug_log()
{
    global $DEBUG_LOG;
    return $DEBUG_LOG;
}
?>
