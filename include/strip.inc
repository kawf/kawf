<?php

function demoronize($string)
{
  $string = preg_replace("/\x09/", "        ", $string);

  /* Remove any and all non-ISO Microsoft extensions */
  $string = preg_replace("/\x82/", ",", $string);
  $string = preg_replace("/\x83/", "<em>f</em>", $string);
  $string = preg_replace("/\x84/", ",,", $string);
  $string = preg_replace("/\x85/", "...", $string);

  $string = preg_replace("/\x88/", "^", $string);
  $string = preg_replace("/\x89/", " °/°°", $string);

  $string = preg_replace("/\x8B/", "<", $string);
  $string = preg_replace("/\x8C/", "Oe", $string);

  $string = preg_replace("/\x91/", "`", $string);
  $string = preg_replace("/\x92/", "'", $string);
  $string = preg_replace("/\x93/", "\"", $string);
  $string = preg_replace("/\x94/", "\"", $string);

  $string = preg_replace("/\x95/", "*", $string);
  $string = preg_replace("/\x96/", "-", $string);
  $string = preg_replace("/\x97/", "--", $string);
  $string = preg_replace("/\x98/", "<sup>~</sup>", $string);
  $string = preg_replace("/\x99/", "<sup>TM</sup>", $string);

  $string = preg_replace("/\x9B/", ">", $string);
  $string = preg_replace("/\x9C/", "oe", $string);

  return $string;
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
  "font" => array(array("color=", true, validate_null), "/font"),
  "b" => array(array(), "/b"),
  "i" => array(array(), "/i"),
  "u" => array(array(), "/u"),
  "tt" => array(array(), "/tt"),
  "big" => array(array(), "/big"),
  "small" => array(array(), "/small"),
  "pre" => array(array(), "/pre"),
  "sub" => array(array(), "/sub"),
  "sup" => array(array(), "/sup"),
  "center" => array(array(), "/center"),
  "embed" => array(array(), "/embed"),
  "object" => array(array(), "/object"),
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

function striptag($message, $allowed_tags)
{
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
  $start = $n_message;
  while (strlen($n_message)>0) {
    foreach ($allowed_tags as $tag => $etag) {
      $n_message = str_ireplace("<$tag></$tag>", "", $n_message);
    }
    /* we have converged. stop */
    if ($start == $n_message) break;
  }

  return $n_message;
}

?>
