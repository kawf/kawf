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
        if ($pos === false)
          /* Failed, so find the next blank character and leave it long */
          $pos = strpos(chop($str), " ");

        if ($pos === false)
          /* Really nothing more, we're done */
          break;

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
