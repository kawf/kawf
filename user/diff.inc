<?php
function diff($old, $new)
{
  $diff = '';

  /* TODO: if pear is installed */
  /*
  {
    require_once("Text/Diff.php");
    require_once("Text/Diff/Renderer/unified.php");

    $td = &new Text_Diff('auto', array($old, $new));
    $rend = &new Text_Diff_Renderer_unified();
    $diff = $rend->render($td);
  }
  */

  if(!ini_get('safe_mode')) {
    $origfn = tempnam("/tmp/kawf", "kawf");
    $newfn = tempnam("/tmp/kawf", "kawf");

    $origfd = fopen($origfn, "w+");
    $newfd = fopen($newfn, "w+");

    if($origfd && $newfd) {

      fwrite($origfd, implode("\n", $old)."\n");
      fwrite($newfd, implode("\n", $new)."\n");
  
      fclose($origfd);
      fclose($newfd);

      $diff = `diff -u $origfn $newfn`;
    }

    unlink($origfn);
    unlink($newfn);
  }

  /* The first 2 lines don't mean anything to us since it's just temporary */
  /*  filenames */
  $diff = preg_replace("/^--- [^\n]+\n\+\+\+ [^\n]+\n/", "", $diff);

  return $diff;
}
?>
