<?php

require_once("strip.inc");

/*
   hack to pull out header information that is hardcoded into the .tpl
   rather than passed into mailfrom

   note that this passes stuff by reference, such that if no header like
   it is found in the message, the variable is not modified.
   also note that the preg match has a ^ at the beginning, so header
   parsing is VERY picky.. must start at the FIRST char or nothing will work
*/
function extract_header(&$message,$tag,&$value) 
{
   $ret=preg_match("/^$tag: ([^\n]*)\n/",$message, $matches);
   if($ret==1) {
       $value=$matches[1]; 
       if(isset($value))
	   $message=ereg_replace("(\n*)$tag:[^\n]*\n",'\\1',$message);
   }
   return $value;
}

function mailfrom($fromaddress, $toaddress, $message)
{
   $xmailer .= "PHP/".phpversion()."\n";

   extract_header($message,'From',$fromaddress);
   extract_header($message,'To',$toaddress);
   extract_header($message,'Subject',$subject);
   extract_header($message,'X-Mailer',$xmailer);

   $headers  = "MIME-Version: 1.0\n";
   $headers .= "Content-type: text/plain; charset=iso-8859-1\n";
   $headers .= "X-Mailer: $xmailer\n";
   $headers .= "From: \"".$domain."Forum Accounts\" <".$fromaddress.">\n";

   if(!mail($toaddress, $subject, $message, $headers))
       err_not_found("from=$fromaddress\nsub=$subject\nt=$toaddress\nhedr=$headers\nmesg=$message\n");
   return true;
}
?>
