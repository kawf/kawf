<?php

require_once("strip.inc");

function mailfrom($fromaddr, $toaddr, $message)
{
  global $mail_executable, $smtp_server;

  $message = stripspaces($message);

  /* FIXME: Finish implementing this */
  if (isset($mail_executable) || 1) {
    $fp = popen("/usr/sbin/sendmail -f" . $fromaddr. " " . addslashes($toaddr), "w");
    if (!$fp)
      return -1;

    fputs($fp, $message . "\n");
    return pclose($fp);
  } else if (isset($smtp_server)) {
  } else {
    echo "No mail configuration specified\n";
    return -1;
  }

  return -1;
}

?>
