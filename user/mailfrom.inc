<?php
function mailfrom($fromaddress, $toaddress, $subject, $body, $headers) {
  $fp = popen('/usr/sbin/sendmail -f'.$fromaddress.' '.$toaddress,"w");
  if (!$fp)
    return false;

  fputs($fp, "To: $toaddress\n");
  fputs($fp, "Subject: $subject\n");
  fputs($fp, $headers."\n\n");
  fputs($fp, $body);
  fputs($fp, "\n");
  pclose($fp);
  return true;
}
?>
