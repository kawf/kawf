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

function softbreakword(&$Word, $breakStr = "<wbr>", $skiptags = 1)
{
    $intag=0;

    // echo "breaking $Word\n";

    for($i=0;$i<strlen($Word);$i++) {
	if($skiptags) {
	    if($Word{$i}=='<') {
		$intag++;
		$newword.=$Word{$i};
		continue;
	    }
	    if($Word{$i}=='>') {
		if($intag>0) $intag--;
		$newword.=$Word{$i};
		continue;
	    }
	    if($intag) {
		$newword.=$Word{$i};
		continue;
	    }
	}
	$newword.=$Word{$i}.$breakStr;
	$correction+=strlen($breakStr);
    }
    $Word=$newword;
    return $correction;
}

function softbreaklongwords($String, $Maxlen = 78, $breakStr = "<wbr>", $skiptags = 1)
{
    $wordlen=0;
    $intag=0;
    $startpos=0;
    for($i=0;$i<strlen($String);$i++) {
	if((!$intag && ctype_space($String{$i})) || $i+1==strlen($String)) {
	    if($long) {
		// found the end of a long word, process 
		$prefix=substr($String,0,$startpos);
		$longword=substr($String,$startpos,$i-$startpos-1);
		$postfix=substr($String,$i-1);
		//echo "prefix '$prefix'\n";
		//echo "longword '$longword'\n";
		//echo "postfix '$postfix'\n";
		assert(strlen($longword)>$Maxlen);
		$correction=softbreakword($longword, $breakStr, $skiptags);
		$String=$prefix.$longword.$postfix;
		$i+=$correction;
	    }
	    $wordlen=0;
	    $long=0;
	    $startpos=$i+1;
	    continue;
	}
	if($skiptags) {
	    if($String{$i}=='<') {
		$intag++;
		continue;
	    }
	    if($String{$i}=='>') {
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
