<?php

/* We use the templates for everything */
require_once("template.inc");

$server_name=$_SERVER['SERVER_NAME'];
$server_port=$_SERVER['SERVER_PORT'];

$remote_addr=$_SERVER['REMOTE_ADDR'];

$http_host=$_SERVER['HTTP_HOST'];
$script_name=$_SERVER['SCRIPT_NAME'];
$path_info=$_SERVER['PATH_INFO'];

$query_string=$_SERVER['QUERY_STRING'];

function dump_env()
{
    global $server_name, $server_port, $remote_addr;
    global $http_host, $script_name, $path_info, $query_string;

    echo "server_name = '$server_name'<br>\n";
    echo "server_port = '$server_port'<br>\n";
    echo "remote_addr = '$remote_addr'<br>\n";
    echo "http_host = '$http_host'<br>\n";
    echo "script_name = '$script_name'<br>\n";
    echo "path_info = '$path_info'<br>\n";
    echo "query_string = '$query_string'<br>\n";
}

function err_not_found($description = "")
{
  global $_SERVER;
  global $template_dir, $srcroot;
  global $server_name, $server_port;
  global $script_name, $path_info;

  Header("HTTP/1.0 404 Not found");

  /* dump_env(); */

  if (!isset($template_dir))
    $tpl = new Template($srcroot . "/php/templates", "comment");
  else
    $tpl = new Template($template_dir, "comment");

  $tpl->set_file("errnotfound", "404.tpl");

  $tpl->set_var(array(
    "DESCRIPTION" => $description,
    "URL" => $script_name . $path_info,
    "SERVER_SOFTWARE" => $_SERVER['SERVER_SOFTWARE'],
    "SERVER_NAME" => $server_name,
    "SERVER_PORT" => $server_port,
  ));

  $tpl->pparse("content", "errnotfound");

  exit;
}

function getmicrotime()
{
  $mtime = explode(" ", microtime());
  return intval($mtime[0] * 1000000);
}

/* Seed the random number generator */
mt_srand(getmicrotime());

?>
