<?php

/* We use the templates for everything */
require("template.inc");

function err_not_found($description = "")
{
  global $template_dir, $srcroot;
  global $SCRIPT_NAME, $PATH_INFO, $SERVER_SOFTWARE, $SERVER_NAME, $SERVER_PORT;

  Header("HTTP/1.0 404 Not found");

  if (!isset($template_dir))
    $tpl = new Template($srcroot . "/php/templates", "comment");
  else
    $tpl = new Template($template_dir, "comment");

  $tpl->set_file("errnotfound", "404.tpl");

  $tpl->set_var(array(
    "DESCRIPTION" => $description,
    "URL" => $SCRIPT_NAME . $PATH_INFO,
    "SERVER_SOFTWARE" => $SERVER_SOFTWARE,
    "SERVER_NAME" => $SERVER_NAME,
    "SERVER_PORT" => $SERVER_PORT,
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
