<?php
function nl2brPre($string, $xhtml=false)
{
    if ($xhtml)
	$br = '<br />';
    else
	$br = '<br>';

    // First, check for <pre> tag
    if(strpos($string, "<pre>") === false)
    {
        // return nl2br($string, $xhtml); // php 5.3.x
        return nl2br($string);
    }

    if(strpos($string, "<p>") !== false)
	$p = true;

    // If there is a <pre>, we have to split by line
    // and manually replace the linebreaks with <br />
    $strArr=explode("\n", $string);
    $output="";
    $preFound=3;

    // Loop over each line
    foreach($strArr as $line)
    {    // See if the line has a <pre>. If it does, set $preFound to true
        if(strpos($line, "<pre>") !== false) {
            $preFound=1;
        } elseif(strpos($line, "</pre>") !== false) {
            $preFound=2;
        }

        // If we are in a pre tag, just give a \n, else a <br />
        switch($preFound) {
            case 1: // found a <pre> tag, close the <p> element
		if ($p) $output .= "</p>\n";
                $output .= $line;
                break;
            case 2: // found the closing </pre> tag, append a newline and open a new <p> element
                $output .= $line;
                if ($p) $output . "\n<p>";
                $preFound = 3; // switch to normal behaviour
                break;
            case 3: // simply append a <br /> element
                $output .= $line . $br . "\n";
                break;
        }
    }

    return $output;
}
?>
