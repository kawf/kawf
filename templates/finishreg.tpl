{HEADER}

<img src="../pix/finish.gif"><br>

<font face="Verdana, Arial, Geneva">

<?php
if (!isset($user))
  echo "Unkown cookie $cookie<br>\n";
else {
  echo "Registration is complete<p>\n";
  $text = "Thank you for registering at the AudiWorld forums, to complete your registration please select a password and peferences of your choice.";
  include('./prefform.inc');
}
?>

</font>

{FOOTER}
