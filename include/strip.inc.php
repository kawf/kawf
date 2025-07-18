<?php

function debug_hexdump($str)
{
  $out = '';
  foreach (preg_split("//u", $str) as $mbchar) {
    if (strlen($mbchar)>0)
      $out .= $mbchar.'['.bin2hex($mbchar).']';
  }
  return $out;
}

function repl($var, $table)
{
  foreach ($table as $k => $v) {
    $var = str_replace($k, $v, $var);
  }
  return $var;
}

/* try to recover badly demoronized string - used in fetch_message()*/
function remoronize($var)
{
  /* undo incorrect demoronizing */
  $trans_table= array(
    chr(0xe2).chr(0x80).'\'' => '\'',	// low-9, left or right single, don't know which
    //chr(0xe2).chr(0x80).'"' => chr(0xe2).chr(0x80).chr(0x9a),	// probably got demoronized from mdash
    chr(0xe2).chr(0x80).'"' => '&mdash;',	// 0x93 and 0x94 got demoronized below to '"'
    chr(0xe2).chr(0x80).'...' => chr(0xe2).chr(0x80).chr(0xa6),
    chr(0xe2).chr(0x80).'-' => chr(0xe2).chr(0x80).chr(0x93),
    chr(0xe2).chr(0x80).'--' => chr(0xe2).chr(0x80).chr(0x94),
    chr(0xe2).chr(0x80).'oe' => chr(0xe2).chr(0x80).chr(0x9c), // left double
    chr(0xe2).chr(0x80).'<sup>TM</sup>' => chr(0xe2).chr(0x80).chr(0x99), // right single
    //chr(0xef).chr(0xbf).chr(0xbd).'\'' => chr(0xc6).chr(0x92), // florin (f with hook)
    //chr(0xc6).'\'' => chr(0xc6).chr(0x92),	// florin (f with hook)
  );

  return repl($var, $trans_table);
}

function trans($var, $table)
{
  $out = '';
  foreach (preg_split("//u", $var) as $mbchar) {
    if (strlen($mbchar)>0 && array_key_exists($mbchar, $table))
      $out .= $table[$mbchar];
    else
      $out .= $mbchar;
  }
  return $out;
}

/* only used when storing a string */
function demoronize($var)
{
  $trans_table = array(
    /* multibyte to ASCII */
    chr(0xe2).chr(0x80).chr(0x9a) => '\'', //SINGLE LOW-9 QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0x9e) => '"', //DOUBLE LOW-9 QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0xa6) => '...', //HORIZONTAL ELLIPSIS
    chr(0xe2).chr(0x80).chr(0x98) => '\'', //LEFT SINGLE QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0x99) => '\'', //RIGHT SINGLE QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0x9c) => '"', //LEFT DOUBLE QUOTATION MARK - warning, incorrect demoronizing possible
    chr(0xe2).chr(0x80).chr(0x9d) => '"', //RIGHT DOUBLE QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0x93) => '-', //EN DASH - warning, incorrect demoronizing possible
    chr(0xe2).chr(0x80).chr(0x94) => '&mdash;', //EM DASH - warning, incorrect demoronizing possible

    /* strangers found in the wild */
    chr(0xe2).chr(0x84).chr(0xa2) => '<sup>TM</sup>',
    chr(0xc2).chr(0xae) => '&reg;',

    /* fixup a few selected non-ISO Microsoft extensions */
    chr(0x82) =>',',
    chr(0x83) =>'<em>f</em>',
    chr(0x84) =>',,',
    chr(0x85) =>'...',

    chr(0x88) =>'^',
    //chr(0x89) =>'&deg;/&deg;&deg;',	// o/oo

    chr(0x8B) =>'<',
    //chr(0x8C) =>'Oe',

    //chr(0x91) =>'`',  // warning, might alter mb chars with this in it
    //chr(0x92) =>'\'',	// warning, might alter mb chars with this in it
    //chr(0x93) =>'"',	// warning, might alter mb chars with this in it
    //chr(0x94) =>'"',	// warning, might alter mb chars with this in it

    chr(0x95) =>'*',
    chr(0x96) =>'-',
    chr(0x97) =>'&mdash;',
    //chr(0x98) =>'<sup>~</sup>', // warning, might alter mb chars with this in it
    //chr(0x99) =>'<sup>TM</sup>', // warning, might alter mb chars with this in it

    chr(0x9b) =>'>',
    //chr(0x9c) =>'oe',	// warning, might alter mb chars with this in it

    /* make sure to add to strip below */
    chr(0xa9) =>'&copy;',
    chr(0xae) =>'&reg;',

    chr(0xb0) =>'&deg;',
    chr(0xb4) =>'\'',

    /* expand tabs */
    chr(0x09) =>'        ',
  );

  return trans($var, $trans_table);
}

/* try to recover badly demoronized string - used in fetch_message()*/
function utf8ize($string)
{
  /* Workaround for issue #38 - default mysql collation is latin1_swedish,
     which means single byte characters, some of which are not utf8. */
  if (is_valid_utf8($string))
     return $string;

  /* contains non-UTF8 - try to convert it */
  return mb_convert_encoding($string, 'UTF-8', 'Windows-1252,Windows-1251,ISO-8859-1,ASCII,8bit');
}

/* For text strings */
function stripcrap($string, $tags=null)
{
  $string = demoronize($string);
  // $string = utf8ize($string); // Removed call to problematic function
  $string = striptag($string, $tags);
  $string = trim($string);

  return $string;
}

/* For URL's */
function stripcrapurl($string)
{
  $string = striptag($string);
  $string = trim($string);
  $string = preg_replace("/ /", "%20", $string);

  return $string;
}

/* fix bug 2969654 - handle quotes in urls */
function escapequotes($url) { return str_replace('"','%22',$url); }
function unescapequotes($url) { return str_replace('%22','"',$url); }

/* postform processing */
function escape_form($msg)
{
  if (!isset($msg)) return '';

  $msg = preg_replace("/&lt;/", "<", $msg);
  $msg = preg_replace("/&gt;/", ">", $msg);
  $msg = preg_replace("/\"/", "&quot;", $msg);

  return $msg;
}

function escape_form_url($url)
{
  if (!isset($url)) return '';

  $url = preg_replace("/&/", "&amp;",  $url);
  $url = preg_replace("/\"/", "&quot;", $url);

  return $url;
}

$valid_transports = array(
  "http" => true,
  "https" => true,
  "ftp" => true,
  "news" => true,
  "mailto" => true,
  "javascript" => false,
);

function validate_url($url)
{
  global $valid_transports;

  if (!preg_match("/^([^:]+):.*/", $url, $regs))
    return true;

  if (!isset($valid_transports[strtolower($regs[1])]) ||
	!$valid_transports[strtolower($regs[1])])
    return false;

  return true;
}

function normalize_url_scheme($url)
{
  if (!validate_url($url)) return '';

  if (preg_match("#^[^/]*:#", $url))
    return $url;

  if (preg_match("#^//#", $url))
    return "http:$url";

  if (preg_match("#^/#", $url))
    return "http:/$url";

  return "http://$url";
}

function validate_number($number)
{
  return (strspn($number, "0123456789") == strlen($number));
}

function validate_target($target)
{
  $target = strtolower($target);

  if ($target == "_new" || $target == "_top" || $target == "_blank")
    return true;

  return false;
}

function validate_controls($controls)
{
  $controls = strtolower($controls);

  if ($controls == "controls")
    return true;

  return false;
}

function validate_null()
{
  return true;
}

// Things allowed in the subject line
$subject_tags = array(
/*
  "b" => array(array(), "/b"),
  "i" => array(array(), "/i"),
  "em" => array(array(), "/em"),
*/
  "sub" => array(array(), "/sub"),
  "sup" => array(array(), "/sup"),
);

// Things allowed in the message body
$standard_tags = array(
  "b" => array(array(), "/b"),
  "i" => array(array(), "/i"),
  "u" => array(array(), "/u"),
  "a" => array(array("href=", true, 'validate_url', "target=", false, 'validate_target'), "/a"),
  "img" => array(array("src=", true, 'validate_url', "alt=", false, 'validate_null', "width=", false, 'validate_number', "height=", false, 'validate_number')),
  "br" => array(array(), ""),
  //"p" => array(array(), "/p"), // Don't allow this so user can't fool image_url_hack_extract()
  "pre" => array(array(), "/pre"),
  "code" => array(array(), "/code"),
  "blockquote" => array(array(), "/blockquote"),
  "ul" => array(array(), "/ul"),
  "ol" => array(array(), "/ol"),
  "li" => array(array(), "/li"),
  "em" => array(array(), "/em"),
  "strong" => array(array(), "/strong"),
  "tt" => array(array(), "/tt"),
  "cite" => array(array(), "/cite"),
  "sub" => array(array(), "/sub"),
  "sup" => array(array(), "/sup"),
  "center" => array(array(), "/center"),
  "small" => array(array(), "/small"),
  "embed" => array(array("src=", true, 'validate_url', "type=", false, 'validate_null', "width=", false, 'validate_number', "height=", false, 'validate_number')),
  "object" => array(array("data=", true, 'validate_url', "type=", false, 'validate_null', "width=", false, 'validate_number', "height=", false, 'validate_number'), "/object"),
);

$no_tags = array(
  array()
);

function parse_tag($str)
{
  /* Find either the closing tag, or whitespace */
  $closepos = strpos($str, '>');
  if (is_bool($closepos) && !$closepos)
    return array(false, 1);

  /* Mop up any beginning whitespace */
  $pos = 1;
  while ($pos < $closepos && strcspn(substr($str, $pos, 1), " \t\n") == 0)
    $pos++;

  $equal = 0;
  $i = 0;

  /* Tokenize all of the values */
  while ($pos < $closepos) {
    $c = substr($str, $pos, 1);
    $bpos = $pos;

    /* Find a token */
    if ($equal)
      $fpos = strcspn(substr($str, $pos), " \t\n>\"");
    else
      $fpos = strcspn(substr($str, $pos), " \t\n>=\"");
    if (is_bool($fpos) && !$fpos) {
      $pos = $closepos;
      break;
    }

    $pos += $fpos;
    $c = substr($str, $pos, 1);

    if ($c == '"' || $c == '\'') {
      /* Find the closing quote now */
      /* FIXME: Check for escaped quotes */
      $epos = strpos(substr($str, $pos + 1), $c);
      if (is_bool($epos) && !$epos)
        return array(false, $pos);

      $pos += $epos + 2;

      $epos = strpos(substr($str, $pos), '>');
      if (is_bool($epos) && !$epos)
        return array(false, $pos);

      $closepos = $epos + $pos;
    }

    /* Strip off any quotes */
    /* FIXME: Match pairs of quotes, handle escapes correctly */
    $s = "";
    $ppos = $bpos;
    $epos = strcspn(substr($str, $ppos), "'\"");
    while (!is_bool($epos) && ($epos + $ppos) < $pos) {
      $s .= substr($str, $ppos, $epos);
      $ppos += ($epos + 1);
      $epos = strcspn(substr($str, $ppos, $pos - $epos), "'\"");
    }
    $s .= substr($str, $ppos, $pos - $ppos);

    if ($equal) {
      $attr['val'][$i++] = trim($s);
      $equal = 0;
    } else
      $attr['attr'][$i++] = $s;

    /* Mop up the space at the end */
    while ($pos < $closepos && strcspn(substr($str, $pos, 1), " \t\n") == 0)
      $pos++;

    if (substr($str, $pos, 1) == '=') {
      $equal = 1;

      $pos++;
      while ($pos < $closepos && strcspn(substr($str, $pos, 1), " \t\n") == 0)
        $pos++;

      $i--;
    }
  }

  $pos++;
  $attr['str'] = substr($str, 0, $pos);

  return array(true, $pos, $attr);
}

function convert_brackets($string)
{
  /* Escape out some standard HTML */
  $string = preg_replace("/&/", "&amp;", $string);
  $string = preg_replace("/</", "&lt;", $string);
  $string = preg_replace("/>/", "&gt;", $string);

  /* Undo some "special" cases */
  $string = preg_replace("/&amp;lt;/", "&lt;", $string);
  $string = preg_replace("/&amp;gt;/", "&gt;", $string);
  $string = preg_replace("/&amp;nbsp;/", "&nbsp;", $string);
  $string = preg_replace("/&amp;copy;/", "&copy;", $string);
  $string = preg_replace("/&amp;reg;/", "&reg;", $string);
  $string = preg_replace("/&amp;deg;/", "&deg;", $string);
  $string = preg_replace("/&amp;frac12;/", "&frac12;", $string);
  $string = preg_replace("/&amp;frac14;/", "&frac14;", $string);
  $string = preg_replace("/&amp;mdash;/", "&mdash;", $string);
  $string = preg_replace("/&amp;amp;/", "&amp;", $string); // Always last

  return $string;
}

function validate_tag($elements, $curpos, $allowed_tags)
{
  $element = $elements[$curpos];

  $skip = 0;

  $allowed = isset($allowed_tags[strtolower($element['attr'][0])]);
  if (!$allowed)
    return array(convert_brackets($element['str']), 0);

  $tags = $allowed_tags[strtolower($element['attr'][0])];

  $message = "<";
  $message .= $element['attr'][0];

  //reset($tags[0]);
  //while (list(, $attr) = each($tags[0])) {
  //  list(, $required) = each($tags[0]);
  //  list(, $validate_func) = each($tags[0]);
  for (reset($tags[0]); $attr = current($tags[0]); next($tags[0])) {
    $required = next($tags[0]);
    $validate_func = next($tags[0]);

    $accepted = 0;

    //reset($element['attr']);
    //next($element['attr']);	/* Skip the first attribute */
    //while (list($key, $val) = each($element['attr'])) {
    foreach ($element['attr'] as $key => $val) {
      if ($key == 0) continue;    /* Skip the first attribute */
      if (substr($attr, -1) == '=') {
        /* Ignore empty attribute values */
        if (!strlen($element['val'][$key]))
          break;

        if (strtolower($val) == substr($attr, 0, -1)) {
          if (!$validate_func($element['val'][$key]))
            break;

          $message .= " $val=\"";
          $message .= $element['val'][$key];
          $message .= "\"";
          $accepted = 1;
          break;
        }
      } else {
        if (strtolower($val) == $attr) {
          $message .= " $val";
          $accepted = 1;
          break;
        }
      }
    }

    if (!$accepted && $required)
      return array(convert_brackets($element['str']), 0);
  }

  /* Deal with XML style terminating /'s correctly */
  /* FIXME: Lame, but list(, $val) = end($element['attr']); doesn't seem */
  /*  to work */
  //$val = "";
  //reset($element['attr']);
  //next($element['attr']);	/* Skip the first attribute */
  //while (list(, $oval) = each($element['attr']))
  //  $val = $oval;
  $val = end($element['attr']);

  if ($val == "/" && !isset($tags[1]))
    $message .= " /";
  $message .= ">";

  if (isset($tags[1])) {
    /* Find the closing tag */
    for ($i = $curpos + 1; $i < count($elements); $i++) {
      if (is_array($elements[$i])) {
        if (strtolower($elements[$i]['attr'][0]) != "/" . strtolower($element['attr'][0])) {
          if (substr($elements[$i]['attr'][0], 0, 1) == "/")
            return array(convert_brackets($element['str']), 0);

          list($str, $j) = validate_tag($elements, $i, $allowed_tags);

          $message .= $str;
          $i += $j;
        } else
          break;
      } else
        $message .= convert_brackets($elements[$i]);
    }

    if ($i >= count($elements))
      return array(convert_brackets($element['str']), 0);

    $skip = $i - $curpos;
    $message .= "<";
    $message .= $elements[$i]['attr'][0];
    $message .= ">";
  }

  return array($message, $skip);
}

function entity_decode($message)
{
  /* Make sure people dont try to trick striptag() with &#xx; constructions.
     Should this be recursive? */
  /* Works like html_entity_decode, but ';' is optional. */
  $message = preg_replace_callback('~&#x([0-9a-f]+);?~i', function($matches) {return chr(hexdec($matches[1]));}, $message);
  $message = preg_replace_callback('~&#([0-9]+);?~', function($matches) {return chr($matches[1]);}, $message);
  return $message;
}

function striptag($message, $allowed_tags = null)
{
  /* Make sure people dont try to trick striptag() with &#xx; constructions. */
  $message = entity_decode($message);

  // If no allowed_tags specified, use no tags (strip all tags)
  if ($allowed_tags === null) {
    $allowed_tags = array(array());
  }

  /*
   * Each element in the allowed_tags array is the tag body, without the <>
   * (eg; '/i' for the </I> tag), and case is irrelevant.
   */
  $pos = 0;

  /* Split out the entire string into text and tags (w/ attributes) */
  while (1) {
    /* Find the beginning of the next tag */
    $openpos = strpos(substr($message, $pos), '<');
    if (is_bool($openpos) && !$openpos)
      break;

    /* Find the beginning of the next tag */
    if ($openpos > 0)
      $elements[] = substr($message, $pos, $openpos);

    list($valid, $closepos, $attr) = parse_tag(substr($message, $openpos + $pos));

    if ($valid)
      $elements[] = $attr;
    else
      $elements[] = convert_brackets(substr($message, $pos + $openpos, $closepos));

    $pos += $openpos + $closepos;
  }

  $elements[] = substr($message, $pos);

  $n_message = '';
  for ($i = 0; $i < count($elements); $i++) {
    $element = $elements[$i];

    if (is_array($element)) {
      list($str, $skip) = validate_tag($elements, $i, $allowed_tags);

      $n_message .= $str;
      $i += $skip;
    } else
      $n_message .= convert_brackets($element);
  }

  /* prevent ppl from making empty nested tags, eg <sub><sup></sup></sub> */
  while (strlen($n_message)>0) {
    $last = $n_message;
    foreach ($allowed_tags as $tag => $etag) {
      $n_message = preg_replace("#<$tag>\s*</$tag>#i", "", $n_message);
    }
    /* we have converged. stop */
    if ($last == $n_message) break;
  }

  return $n_message;
}
// vim:sw=2
?>
