<?php

include_once("strip.inc");

function mailfrom($fromaddr, $toaddr, $message)
{
  global $mail_executable, $smtp_server;

  $message = stripspaces($message);

  /* FIXME: Finish implementing this */
  if (isset($mail_executable) || 1) {
    $fp = popen("/usr/lib/sendmail -f" . $fromaddr. " " . addslashes($toaddr), "w");
    if (!$fp)
      return false;

    fputs($fp, $message . "\n");
    pclose($fp);
  } else if (isset($smtp_server)) {
  } else {
    echo "No mail configuration specified\n";
    return false;
  }

  return true;
}

?>
