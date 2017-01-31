<?php
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

/* emulate RewriteRule  ^/(pics/.*|css/.*|scripts/.*|robots.txt|favicon.ico|apple-touch-icon.png)$ /$1 */
if (preg_match('@^/(\.well-known\/.*|pics/.*|robots\.txt|favicon\.ico|apple-touch-icon\.png)$@',
    $path, $matches)) {
    header('Content-Type: '. getMimeType($matches[1]));
    require $matches[1];
    return;
}

if (preg_match('@^/(css/.*)$@', $path, $matches)) {
    $file = $matches[1];
    header('Content-Type: text/css');
    require $file;
    return;
}

if (preg_match('@^/(scripts/.*)$@', $path, $matches)) {
    $file = $matches[1];
    header('Content-Type: application/javascript');
    require $file;
    return;
}

/* emulate RewriteRule ^/(account|admin|tips)/.*$ /$1.php [L] */
if (preg_match('@^/(account|admin|tips)/.*$@', $path, $matches)) {
    require $matches[1] . '.php';
    return;
}

/* fallthrough to main.php */
include('setup.inc');
include("$srcroot/user/main.php");
?>
