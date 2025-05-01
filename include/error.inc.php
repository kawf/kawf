<?php

error_reporting(E_ALL & ~E_NOTICE);

// Global callback for error page rendering
$error_page_renderer = function($error_data) {
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
};

function set_error_page_renderer($callback) {
    global $error_page_renderer;
    $error_page_renderer = $callback;
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

function err_not_found($description = "")
{
    global $_SERVER;
    global $template_dir, $srcroot, $default_skin;
    global $server_name, $server_port;
    global $request_uri;
    global $error_page_renderer;

    // Set 404 status for all cases
    Header("HTTP/1.0 404 Not found");

    // Prepare error data
    $error_data = array(
        'title' => '404 Not Found',
        'description' => $description,
        'uri' => htmlspecialchars($request_uri ?? 'Unknown URI', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'server_info' => array(
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown Server',
            'name' => $server_name ?? 'Unknown Host',
            'port' => $server_port ?? 'Unknown Port'
        )
    );

    print $error_page_renderer($error_data);
    exit;
}

// vim: sw=2
?>
