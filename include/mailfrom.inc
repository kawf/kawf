<?php

require_once("strip.inc");

/*
   hack to pull out header information that is hardcoded into the .tpl
   rather than passed into mailfrom
*/
function mailfrom($fromaddress, $toaddress, $message) {
  global $domain;
  $xmailer = "PHP/" . phpversion() . "\n";
  $subject = "";

  list($oheader, $obody) = explode("\n\n", $message, 2);
  if($obody) {
    // Extract headers to use.
    $oheadermap = array();
    foreach(explode("\n", $oheader) as $line) {
      list($key, $value) = explode(": ", $line);
      $oheadermap[$key] = $value;
    }
    if($oheadermap["From"]) $fromaddress = $oheadermap["From"];
    if($oheadermap["To"]) $toaddress = $oheadermap["To"];
    if($oheadermap["Subject"]) $subject = $oheadermap["Subject"];
    if($oheadermap["X-Mailer"]) $xmailer = $oheadermap["X-Mailer"];
  } else {
    // We got just one element, which means there are no headers.
    $obody = $oheader;
  }

  $headers  = "MIME-Version: 1.0\n";
  $headers .= "Content-type: text/plain; charset=iso-8859-1\n";
  $headers .= "X-Mailer: $xmailer\n";
  $headers .= "From: \"".$domain."Forum Accounts\" <".$fromaddress.">\n";

  if(!mail($toaddress, $subject, $obody, $headers))
    err_not_found("from=$fromaddress\nsub=$subject\nt=$toaddress\nhedr=$headers\nmesg=$message\n");
  return true;
}
?>
