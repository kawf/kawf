<?php

/* We use the templates for everything */
require_once("template.inc");

function err_not_found($description = "")
{
  global $template_dir, $srcroot;
  global $HTTP_SERVER_VARS;

  Header("HTTP/1.0 404 Not found");

  if (!isset($template_dir))
    $tpl = new Template($srcroot . "/php/templates", "comment");
  else
    $tpl = new Template($template_dir, "comment");

  $tpl->set_file("errnotfound", "404.tpl");

  $tpl->set_var(array(
    "DESCRIPTION" => $description,
    "URL" => $HTTP_SERVER_VARS['SCRIPT_NAME'] . $HTTP_SERVER_VARS['PATH_INFO'],
    "SERVER_SOFTWARE" => $HTTP_SERVER_VARS['SERVER_SOFTWARE'],
    "SERVER_NAME" => $HTTP_SERVER_VARS['SERVER_NAME'],
    "SERVER_PORT" => $HTTP_SERVER_VARS['SERVER_PORT'],
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
