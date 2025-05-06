<?php
function textwrap($String, $breaksAt = 78, $breakStr = "\n", $padStr = "")
{
  $lines = explode("\n", $String);
  $cnt = count($lines);
  for ($x = 0; $x < $cnt; $x++) {
    if (strlen($lines[$x]) > $breaksAt) {
      $str = $lines[$x];
      while (strlen($str) > $breaksAt) {
        $pos = strrpos(chop(mb_strcut($str, 0, $breaksAt)), " ");
        if ($pos === false)
          /* Failed, so find the next blank character and leave it long */
          $pos = strpos(chop($str), " ");

        if ($pos === false)
          /* Really nothing more, we're done */
          break;

        $newString .= $padStr . mb_strcut($str, 0, $pos) . $breakStr;
        $str = trim(mb_strcut($str, $pos));
      }
      $newString .= $padStr . $str . $breakStr;
    } else
      $newString .= $padStr . $lines[$x] . $breakStr;
  }

  return $newString;
}

function softbreakword(&$Word, $breakStr = '<wbr>', $skiptags = 1)
{
    $intag=0;
    $inamp=0;
    $correction=0;
    $len=strlen($Word);

    // echo "breaking $Word\n";

    for($newword='',$i=0;$i<$len;$i++) {
	if($skiptags) {
	    if($Word[$i]=='<') {
		$intag++;
		$newword.=$Word[$i];
		continue;
	    }
	    if($Word[$i]=='>') {
		if($intag>0) $intag--;
		$newword.=$Word[$i];
		continue;
	    }
	    if($intag) {
		$newword.=$Word[$i];
		continue;
	    }
	    if($Word[$i]=='&' && !$inamp) {
		$inamp=1;
		$newword.=$Word[$i];
		continue;
	    }
	    if($Word[$i]==';' && $inamp) {
		$inamp=0;
		$newword.=$Word[$i];
		continue;
	    }
	    if($inamp) {
		$newword.=$Word[$i];
		continue;
	    }
	}
	$newword.=$Word[$i].$breakStr;
	$correction+=strlen($breakStr);
    }
    $Word=$newword;
    return $correction;
}

// add soft breaks after EVERY character in a long word to let browsers wrap
// in the middle of the word.
// by default, ignore tags, and do not treat them as printable parts of the
// string
function softbreaklongwords($String, $Maxlen = 78, $breakStr = '<wbr>', $skiptags = 1)
{
    $wordlen=0;
    $intag=0;
    $startpos=0;
    $len=strlen($String);
    $long=0;
    for($i=0;$i<$len;$i++) {
	if((!$intag && ctype_space($String[$i])) || $i+1==$len) {
	    if($long) {
		// found the end of a long word, process
		$prefix=mb_strcut($String,0,$startpos);
		$longword=mb_strcut($String,$startpos,$i-$startpos-1);
		$postfix=mb_strcut($String,$i-1);
		//echo '<pre>';
		//echo "prefix '$prefix'\n";
		//echo "longword '$longword'\n";
		//echo "postfix '$postfix'\n";
		//echo '<pre>';
		assert(strlen($longword)>$Maxlen-1);
		$correction=softbreakword($longword, $breakStr, $skiptags);
		$String=$prefix.$longword.$postfix;
		$i+=$correction;
		$len+=$correction;
	    }
	    $wordlen=0;
	    $long=0;
	    $startpos=$i+1;
	    continue;
	}
	if($skiptags) {
	    if($String[$i]=='<') {
		$intag++;
		continue;
	    }
	    if($String[$i]=='>') {
		if($intag>0) $intag--;
		continue;
	    }
	    if($intag) continue;
	}
	$wordlen++;

	if($wordlen>$Maxlen) {
	    if(!$long) {
		// found the start of a long word, mark start
		// echo "found start at $startpos, $wordlen>$Maxlen\n";
		$long=1;
	    }
	}
    }
    return $String;
}
?>
