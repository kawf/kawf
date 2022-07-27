<?php
require_once('setup.inc');
require_once("$srcroot/include/util.inc");

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function getMimeType( $filename ) {
    $realpath = realpath( $filename );
    if ( $realpath
	&& function_exists( 'finfo_file' )
	&& function_exists( 'finfo_open' )
	&& defined( 'FILEINFO_MIME_TYPE' )
    ) {
	// Use the Fileinfo PECL extension (PHP 5.3+)
	return finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $realpath );
    }
    if ( function_exists( 'mime_content_type' ) ) {
	// Deprecated in PHP 5.3
	return mime_content_type( $realpath );
    }
    return false;
}

function readfile_header($file)
{
    /* not needed */
    //header('Content-Description: File Transfer');
    //header('Content-Disposition: attachment; filename="'.basename($file).'"');

    $last_modified_time = filemtime($file); 
    $etag = md5_file($file); 

    if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time || 
        @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
        header("HTTP/1.1 304 Not Modified"); 
        return;
    }
    
    header('Content-Length: ' . filesize($file));
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time)." GMT"); 
    header("Etag: $etag"); 
    header('cache-control: public, max-age=86400, no-cache');
    readfile($file);
}

function read_file($file, $type=null)
{
    if (is_dir($file)) {
	if (is_readable("$file/index.php")) {
	    /* parse as php */
	    require("$file/index.php");
	    return;
	}

	if (is_readable("$file/index.html")) {
	    $file = "$file/index.html";
	    /* output directly */
	    header("Content-Type: text/html");
	    readfile_header($file);
	    return;
	}

	return err_not_found();
    }

    if (!is_readable($file)) return err_not_found();

    if ($type==null) {
	$type=getMimeType($file);
	if (!$type)
	    return err_not_found("\"$file\": Unknown type");
    }

    header("Content-Type: $type");
    if ($type == 'text/x-php') {
	require($file);
    } else {
	readfile_header($file);
    }
}

/* emulate RewriteRule  ^/(pics/.*|css/.*|scripts/.*|robots.txt|favicon.ico|apple-touch-icon.png)$ /$1 */
if (preg_match('@^/(\.well-known/.*|pics/.*|robots\.txt|favicon\.ico|apple-touch-icon\.png)$@',
    $path, $matches)) {
    read_file($matches[1]);
    return;
}

if (preg_match('@^/(css/.*)$@', $path, $matches)) {
    read_file($matches[1], 'text/css');
    return;
}

if (preg_match('@^/(scripts/.*)$@', $path, $matches)) {
    read_file($matches[1], 'application/javascript');
    return;
}

/* emulate RewriteRule ^/(account|admin|tips)/.*$ /$1.php [L] */
if (preg_match('@^/(account|admin|tips)/.*$@', $path, $matches)) {
    require($matches[1] . '.php');
    return;
}

/* fallthrough to main.php */
include("$srcroot/user/main.php");
?>
