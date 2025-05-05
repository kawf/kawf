<?php

error_reporting(E_ALL & ~E_NOTICE);

kawfError::initialize();

final class kawfError {
    // Private constructor to prevent instantiation of this static utility class.
    private function __construct() {}

    static $debug_log = '';

    // Global callback for error page rendering
    static $error_page_renderer;

    static function default_error_page_renderer($error_data) {
        // Default error page renderer - mimics Apache's style
        print "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n";
        print "<html><head>\n";
        print "<title>404 Not Found</title>\n";
        print "</head><body>\n";
        print "<h1>Not Found</h1>\n";
        print "<p>The requested URL " . $error_data['uri'] . " was not found on this server.</p>\n";
        if (!empty($error_data['description'])) {
            print "<p>Error: \"". $error_data['description'] . "\"</p>\n";
        }
        print "<hr>\n";
        print "<address>" . $error_data['server_info']['software'] . " at " . $error_data['server_info']['name'] . " Port " . $error_data['server_info']['port'] . "</address>\n";
        print "</body></html>\n";
        return "";
    }

    public static function initialize(): void {
        // Initialize the error page renderer with the default
        self::$error_page_renderer = [self::class, 'default_error_page_renderer'];
    }
}

function set_error_page_renderer($callback) {
    if (!is_callable($callback)) {
        throw new InvalidArgumentException('Error page renderer must be callable');
    }
    kawfError::$error_page_renderer = $callback;
}

function err_not_found($description = "") {
    global $template_dir, $srcroot, $default_skin;

    // Set 404 status for all cases
    Header("HTTP/1.0 404 Not found");

    // Prepare error data
    $s = get_server();
    $error_data = array(
	'title' => '404 Not Found',
	'description' => $description,
	'uri' => htmlspecialchars($s->requestUri ?? 'Unknown URI', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
	'server_info' => array(
	'software' => $s->serverSoftware ?? 'Unknown Server',
	'name' => $s->name ?? 'Unknown Host',
	'port' => $s->port ?? 'Unknown Port'
	)
    );

    print (kawfError::$error_page_renderer)($error_data);
    exit;
}

function debug_log($msg) {
    kawfError::$debug_log .= $msg;
}

function get_debug_log() {
    return kawfError::$debug_log;
}

// vim: ts=4 sw=4 et:
?>
