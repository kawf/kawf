<?php
function domain_exists($email, $record = 'MX')
{
  if (!str_contains($email, "@")) return false;
  list($user, $domain) = explode('@', $email);
  if (!isset($domain) || $domain == '') return false;
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

function is_valid_utf8($str)
{
    $len = strlen($str);
    for($i = 0; $i < $len; $i++){
        $c = ord($str[$i]);
        if ($c > 128) {
            if (($c > 247)) return false;
            elseif ($c > 239) $bytes = 4;
            elseif ($c > 223) $bytes = 3;
            elseif ($c > 191) $bytes = 2;
            else return false;
            if (($i + $bytes) > $len) return false;
            while ($bytes > 1) {
                $i++;
                $b = ord($str[$i]);
                if ($b < 128 || $b > 191) return false;
                $bytes--;
            }
        }
    }
    return true;
}

?>
