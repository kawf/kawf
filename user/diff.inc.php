<?php
spl_autoload_register(function ($class_name) {
    if (substr($class_name, 0, strlen('Horde_')) != 'Horde_')
	return false;

    $file = substr($class_name, strlen('Horde_'));
    $file = str_replace("_", DIRECTORY_SEPARATOR, $file);
    return include_once($file . '.php');
});

function diff($old, $new)
{
    $td = new Horde_Text_Diff('auto', array($old, $new));
    $rend = new Horde_Text_Diff_Renderer_Unified();
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
