<?php
require("strip.inc");

function mailfrom($fromaddr, $toaddr, $message)
{
  $message = stripspaces($message);

  $fp = popen("/usr/sbin/sendmail -f" . $fromaddr. " " . addslashes($toaddr), "w");
  if (!$fp)
    return false;

  fputs($fp, $message . "\n");
  pclose($fp);

  return true;
}
?>
