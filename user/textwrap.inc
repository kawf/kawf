<?php
function textwrap($String, $breaksAt = 78, $breakStr = "\n", $padStr = "")
{
  $lines = explode("\n", $String);
  $cnt = count($lines);
  for ($x = 0; $x < $cnt; $x++) {
    if (strlen($lines[$x]) > $breaksAt) {
      $str = $lines[$x];
      while (strlen($str) > $breaksAt) {
        $pos = strrpos(chop(substr($str, 0, $breaksAt)), " ");
        $newString .= $padStr . substr($str, 0, $pos) . $breakStr;
        $str = trim(substr($str, $pos));
      }
      $newString .= $padStr . $str . $breakStr;
    } else
      $newString .= $padStr . $lines[$x] . $breakStr;
  }

  return $newString;
}
?>
