<?php
function diff($old, $new)
{
    require_once("Text/Diff.php");
    require_once("Text/Diff/Renderer/unified.php");

    $td = &new Text_Diff('auto', array($old, $new));
    $rend = &new Text_Diff_Renderer_unified();
    return $rend->render($td);
}

function external_diff($old, $new)
{
    /* if safe mode, fall back to builtin diff */
    if(ini_get('safe_mode'))
	return diff($old, $new);

    $origfn = tempnam("/tmp", "kawf");
    $newfn = tempnam("/tmp", "kawf");

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

    /* The first 2 lines don't mean anything to us since it's just temporary
     * filenames */
    return preg_replace("/^--- [^\n]+\n\+\+\+ [^\n]+\n/", "", $diff);
}
?>
