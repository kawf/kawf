<?php
class nl2brPre {
    public static function out($string, $xhtml=false, $verbose=false)
    {
	/* if no <pre> tag, just return normal nl2br */
	if (strpos($string, "<pre>") === false) {
	    // return nl2br($string, $xhtml); // php 5.3.x
	    return nl2br($string);
	}

	if ($verbose) echo "checking ". strlen($string)." byte length '".$this->cook($string)."'\n";

	while ($start < strlen($string)) {
	    $i = nl2brPre::find_tag($string, $pos);
	    $seg = nl2brPre::process(substr($string, $start, $pos-$start), $inp);

	    /* we got the pre in munge if we needed it, advance the ptr */
	    if ($i==-1) $pos += 6;

	    if ($verbose) echo "[$inp $i] [$start->$pos] inspecting '".$this->cook($seg)."'\n";

	    /* dont let inp go negative */
	    $new = $inp + $i;
	    if ($new<0) $new=0;

	    if (($inp <= 1 && $new <= 1 && $inp != $new) || ($i==0)) {
		/* output the segment on all 0->1, 1->0 transitions, or if there
		   are no tags left; then advance start pointer */
		if ($verbose) echo " [$inp->$new] $pos outputting '".$this->cook($seg)."'\n";
		$out .= $seg;
		$start = $pos;
	    } else if ($verbose) {
		echo " [$inp->$new] $pos skipped '".$this->cook($seg)."'\n";
	    }

	    $inp = $new;
	}

	if ($inp>0 && $verbose)
	    if ($verbose) echo "[$inp] tag left open!\n";

	return $out;
    }

    private static function process($seg, $inp)
    {
	if ($inp>0) {
	    $seg = str_replace('<','&lt;',$seg);
	    $seg = str_replace('>','&gt;',$seg);
	    /* close with unmunged tag */
	    $seg .= '</pre>';
	} else {
	    $seg = nl2br($seg);
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

    public static function test($xhtml = true)
    {
	if ($xhtml) $br = "<br />";
	else $br = "<br>";

	$a= array (
	    array("a", "a"),
	    array("a\nb", "a$br\nb"),
	    array("a\nb\n", "a$br\nb$br\n"),
	    array("\na\nb", "$br\na$br\nb"),
	    
	    array("<pre></pre>", "<pre></pre>"),
	    array("<pre>\n</pre>", "<pre>\n</pre>"),
	    array("<pre>a</pre>", "<pre>a</pre>"),
	    array("<pre>a</pre>\n", "<pre>a</pre>$br\n"),
	    array("\n<pre>a</pre>\n", "$br\n<pre>a</pre>$br\n"),
	    array("<pre>a\n</pre>", "<pre>a\n</pre>"),
	    array("<pre>\na</pre>", "<pre>\na</pre>"),
	    array("<pre>b\n</pre>c\n", "<pre>b\n</pre>c$br\n"),
	    array("a\n<pre>b\n</pre>", "a$br\n<pre>b\n</pre>"),
	    array("a\n<pre>b\n</pre>c\n", "a$br\n<pre>b\n</pre>c$br\n"),
	    array("a<pre><pre>b</pre>c</pre>d", "a<pre>&lt;pre&gt;b&lt;/pre&gt;c</pre>d"),

	    array("<pre>a", "<pre>a</pre>"),
	    array("<pre><pre>a", "<pre>&lt;pre&gt;a</pre>"),
	    array("<pre><pre>a</pre>", "<pre>&lt;pre&gt;a&lt;/pre&gt;</pre>"),
	    array("<pre><pre>a</pre></pre>", "<pre>&lt;pre&gt;a&lt;/pre&gt;</pre>"),
	    array("<pre></pre>a</pre>", "<pre></pre>a</pre>"),
	);
	
	foreach ($a as $t) {
	    $out = nl2brPre::out($t[0], $xhtml);
	    if ($out != $t[1]) {
		echo "-----------------------------\n";
		nl2brPre::out(true);
		echo "$i fail '" . nl2brPre::cook($t[0]) . "'\n";
		echo "$i  exp '" . nl2brPre::cook($t[1]) . "'\n";
		echo "$i  got '" . nl2brPre::cook($out) . "'\n";
		echo "-----------------------------\n";
		exit();
	    } else {
		$p++;
	    }
	    $i++;
	}
	echo "$p out of $i tests passed\n";
    }
}

if ($_SERVER['argv'][1] == 'test')
    nl2brPre::test();
?>
