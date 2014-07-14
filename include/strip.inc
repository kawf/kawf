<?php

function demoronize($var)
{
  $trans_table = array(
    /* multibyte to utf8 */
    chr(0xe2).chr(0x80).chr(0x9a) => '\'', //SINGLE LOW-9 QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0x9e) => '"', //DOUBLE LOW-9 QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0xa6) => '...', //HORIZONTAL ELLIPSIS
    chr(0xe2).chr(0x80).chr(0x98) => '\'', //LEFT SINGLE QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0x99) => '\'', //RIGHT SINGLE QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0x9c) => '"', //LEFT DOUBLE QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0x9d) => '"', //RIGHT DOUBLE QUOTATION MARK
    chr(0xe2).chr(0x80).chr(0x93) => '-', //EN DASH
    chr(0xe2).chr(0x80).chr(0x94) => '--', //EM DASH

    /* expand tabs */
    chr(0x09) =>'        ',

    /* fixup a few selected non-ISO Microsoft extensions */
    chr(0x82) =>',',
    chr(0x83) =>'<em>f</em>',
    chr(0x84) =>',,',
    chr(0x85) =>'...',

    chr(0x88) =>'^',
    chr(0x89) =>'&deg;/&deg;&deg;',

    chr(0x8B) =>'<',
    chr(0x8C) =>'Oe',

    chr(0x91) =>'`',
    chr(0x92) =>'\'',
    chr(0x93) =>'\"',
    chr(0x94) =>'\"',

    chr(0x95) =>'*',
    chr(0x96) =>'-',
    chr(0x97) =>'--',
    chr(0x98) =>'<sup>~</sup>',
    chr(0x99) =>'<sup>TM</sup>',

    chr(0x9B) =>'>',
    chr(0x9C) =>'oe',

    /* make sure to add to strip below */
    chr(0xA9) =>'&copy;',
    chr(0xAE) =>'&reg;',

    chr(0xB0) =>'&deg;',
    chr(0xB4) =>'\'',
  );

  foreach ($trans_table as $utf8_code => $replace) {
      $var = str_replace($utf8_code, $replace, $var);
  }

  return $var;
}

/* For text strings */
function stripcrap($string, $tags=null)
{
  global $no_tags;

  if(!isset($tags))
    $tags = $no_tags;

  // $string = preg_replace("/&/", "&amp;", $string);
  $string = demoronize($string);
  $string = striptag($string, $tags);
  $string = trim($string);

  return $string;
}

/* For URL's */
function stripcrapurl($string)
{
  global $no_tags;

  $string = striptag($string, $no_tags);
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

$subject_tags = array(
/*
  "b" => array(array(), "/b"),
  "i" => array(array(), "/i"),
  "em" => array(array(), "/em"),
*/
  "sub" => array(array(), "/sub"),
  "sup" => array(array(), "/sup"),
);

$standard_tags = array(
  "a" => array(array("href=", true, validate_url, "target=", false, validate_target), "/a"),
  "img" => array(array("src=", true, validate_url, "border=", false, validate_number, "alt=", false, validate_null)),
  "video" => array(array("src=", true, validate_url, "controls=", false, validate_controls), "/video"),
  "font" => array(array("color=", true, validate_null), "/font"),
  "b" => array(array(), "/b"),
  "strong" => array(array(), "/strong"),
  "i" => array(array(), "/i"),
  "em" => array(array(), "/em"),
  "u" => array(array(), "/u"),
  "tt" => array(array(), "/tt"),
  "big" => array(array(), "/big"),
  "small" => array(array(), "/small"),
  "pre" => array(array(), "/pre"),
  "sub" => array(array(), "/sub"),
  "sup" => array(array(), "/sup"),
  "center" => array(array(), "/center"),
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

  reset($tags[0]);
  while (list(, $attr) = each($tags[0])) {
    list(, $required) = each($tags[0]);
    list(, $validate_func) = each($tags[0]);

    $accepted = 0;

    reset($element['attr']);
    next($element['attr']);	/* Skip the first attribute */
    while (list($key, $val) = each($element['attr'])) {
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
  $val = "";
  reset($element['attr']);
  next($element['attr']);	/* Skip the first attribute */
  while (list(, $oval) = each($element['attr']))
    $val = $oval;

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

function striptag($message, $allowed_tags)
{
  /* Make sure people dont try to trick striptag() with &#xx; constructions. */
  $message = entity_decode($message);

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
