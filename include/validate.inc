<?php
function domain_exists($email, $record = 'MX')
{
  list($user, $domain) = split('@', $email);
  return checkdnsrr($domain, $record);
}

function is_valid_email($email)
{
  if (!domain_exists($email)) return false;

  return filter_var($email, FILTER_VALIDATE_EMAIL) ? true : false;
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

function is_valid_signed_integer($integer)
{
    if(!is_numeric($integer)) return 0;
    if($integer!=floor($integer)) return 0;
    return 1;
}

function is_valid_integer($integer)
{
    if(!is_valid_signed_integer($integer)) return 0;
    if($integer!=abs($integer)) return 0;
    return 1;
}
?>
