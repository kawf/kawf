<?
function noob($mode, $uid, $posts)
{
    $noob = log10($posts*100/$uid);
    if($mode == 'txt') {
	header("Content-type: text/plain");
	echo "uid $uid, posts $posts: index $noob\n";
    } else if ($mode == 'png') {
	header("Content-type: image/png");
	$im = imagecreate(250,20) or die ("fail");
	$background_color = imagecolorallocate($im, 255,255,255);
	$text_color = imagecolorallocate($im,0,0,0);
	imagestring ($im, 1,5,5, "uid $uid, posts $posts: index $noob", $text_color);
	imagepng($im);
	imagedestroy($im);
    }
}
?>
