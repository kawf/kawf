<?php
function is_valid_email($email)
{
  return eregi("^[_a-z0-9-][._a-z0-9+-]*@[a-z0-9-]+[a-z0-9-]+\.[a-z0-9-]+[.a-z0-9-]+$", $email);
}

function is_valid_filename($filename)
{
  return eregi("^[_a-z0-9][._a-z0-9-]*$", $filename);
}

function strip_filename($filename)
{
  for ($i = 0; $i < strlen($filename); $i++) {
  }

  return $newfilename;
}
?>
