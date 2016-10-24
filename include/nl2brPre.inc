<?php
class nl2brPre {
    private static function nl2br($string, $xhtml=false)
    {
	switch (version_compare(phpversion(), '5.3.0')) {
	    case -1:
		if ($xhtml)
		    return preg_replace("#<br>#","<br />", nl2br($string));
		return nl2br($string);
	    default:
		return nl2br($string, $xhtml);
	}
    }

    public static function out($string, $xhtml=false, $verbose=false)
    {
	/* if no <pre> tag, just return normal nl2br */
	if (strpos($string, "<pre>") === false)
	    nl2brPre::nl2br($string, $xhtml);

	if ($verbose) echo "checking ". strlen($string)." byte length '".nl2brPre::cook($string)."'\n";

	$start = $inp = 0;
	$out = '';
	while ($start < strlen($string)) {
	    $i = nl2brPre::find_tag($string, $pos);
	    $seg = nl2brPre::process(substr($string, $start, $pos-$start), $inp, $xhtml);

	    /* we got the pre in munge if we needed it, advance the ptr */
	    if ($i==-1) $pos += 6;

	    if ($verbose) echo "[$inp $i] [$start -> $pos] inspecting '".nl2brPre::cook($seg)."'\n";

	    /* dont let inp go negative */
	    $new = $inp + $i;
	    if ($new<0) $new=0;

	    if (($inp <= 1 && $new <= 1 && $inp != $new) || ($i==0)) {
		/* output the segment on all 0->1, 1->0 transitions, or if there
		   are no tags left; then advance start pointer */
		if ($verbose) echo " [$inp -> $new] $pos outputting '".nl2brPre::cook($seg)."'\n";
		$out .= $seg;
		$start = $pos;
	    } else if ($verbose) {
		echo " [$inp -> $new] $pos skipped '".nl2brPre::cook($seg)."'\n";
	    }

	    $inp = $new;
	}

	if ($inp>0 && $verbose)
	    if ($verbose) echo "[$inp] tag left open!\n";

	return $out;
    }

    private static function process($seg, $inp, $xhtml)
    {
	if ($inp>0) {
	    $seg = str_replace('<','&lt;',$seg);
	    $seg = str_replace('>','&gt;',$seg);
	    /* close with unmunged tag */
	    $seg .= '</pre>';
	} else {
	    $seg = nl2brPre::nl2br($seg, $xhtml);
	}

	return $seg;
    }

    private static function find_tag($string, &$pos)
    {
	$p = strpos($string, "<pre>", $pos);
	$n = strpos($string, "</pre>", $pos);

	if ($p!==false && ($p<$n || $n===false)) {
	    /* advance to AFTER open tag */
	    $pos = $p+5;
	    return 1;
	}

	if ($n!==false && ($n<$p || $p===false)) {
	    /* point to BEFORE close tag */
	    $pos = $n;
	    return -1;
	}

	$pos = strlen($string);

	return 0;
    }

    private static function cook($string)
    {
	$string = preg_replace("/\n/", "\\n", $string);
	return preg_replace("/\r/", "\\r", $string);
    }
}

?>
