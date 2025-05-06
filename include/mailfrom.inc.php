<?php

require_once("strip.inc.php");

/*
   hack to pull out header information that is hardcoded into the .tpl
   rather than passed into mailfrom
*/
function mailfrom($fromaddress, $toaddress, $message) {
  global $domain;
  $xmailer = "PHP/" . phpversion() . "\n";
  $subject = "";

  // Validate message format - must have headers and body separated by double newline
  $parts = explode("\n\n", $message, 2);
  if (count($parts) !== 2) {
    error_log("Invalid email format - message must have headers and body separated by double newline: " . $message);
    return false;
  }
  list($oheader, $obody) = $parts;

  // Validate headers - must have required fields
  $oheadermap = array();
  foreach(explode("\n", $oheader) as $line) {
    $parts = explode(": ", $line, 2);
    if (count($parts) !== 2) {
      error_log("Invalid header format - each header must be in format 'Name: value': " . $line);
      return false;
    }
    $oheadermap[$parts[0]] = $parts[1];
  }

  // Validate required headers
  $required_headers = array("From", "To", "Subject");
  foreach ($required_headers as $header) {
    if (!isset($oheadermap[$header])) {
      error_log("Missing required header - email must include '$header' header");
      return false;
    }
  }

  // Use headers from message
  $fromaddress = $oheadermap["From"];
  $toaddress = $oheadermap["To"];
  $subject = $oheadermap["Subject"];
  if(isset($oheadermap["X-Mailer"])) $xmailer = $oheadermap["X-Mailer"];

  $headers  = "MIME-Version: 1.0\n";
  $headers .= "Content-type: text/plain; charset=iso-8859-1\n";
  $headers .= "X-Mailer: $xmailer\n";
  $headers .= "From: \"".$domain."Forum Accounts\" <".$fromaddress.">\n";

  if(!mail($toaddress, $subject, $obody, $headers)) {
    error_log("Failed to send email: from=$fromaddress sub=$subject t=$toaddress hdr=$headers");
    return false;
  }
  return true;
}
?>
