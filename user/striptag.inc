<?php
/*
$no_tags = array(
);
*/

$standard_tags = array(
  array("a href=.*", "/a"),
  array("img src=.*"),
  array("font color=.*", "/font"),
  array("b", "/b"),
  array("i", "/i"),
  array("u", "/u"),
  array("tt", "/tt")
);

$no_tags = array(
  array()
);

function stripspaces($string)
{
  /* Beginning */
  if (strlen($string) > 0) {
    $c = substr($string, 0, 1);
    while (($c == " " || $c == "\n") && strlen($string) > 0) {
      $string = substr($string, 1);
      $c = substr($string, 0, 1);
    }
  }

  /* End */
  if (strlen($string) > 0) {
    $c = substr($string, -1);
    while (($c == " " || $c == "\n") && strlen($string) > 0) {
      $string = substr($string, 0, -1);
      $c = substr($string, -1);
    }
  }

  return $string;
}

function striptag($Message, $AllowableHTML) {
# Each element in the AllowableHTML array is the tag body, without the <>
# (eg; '/i' for the </I> tag), and case is irrelevant. You may include
# Posix regexps in the tags. For example, you can use the '.*' (match 0 or
# more of any character) construct so that
#              'a href=.*'
#     matches
#              <A HREF="it/does/not/matter/what" comes=next until-the=greater-than-sign>
#     Improper tag nesting (eg; '<i>...<b>...</i>...</b'>) is detected as well.
#
#     $AllowableHTML = array(array(<start tag>, <end tag, if any>, <tag name, if desired>), ...)


      $StatusCode    = 0;
      $EditedMessage = $Message;

      $BadTags       = ereg_replace('[^<>]', '', $Message);
      $BadTags       = ereg_replace('<>', '', $BadTags);

      if (strlen($Message) == 0) {

         $StatusCode = 1;

      } elseif (strlen($BadTags) > 0) {

         $StatusCode = -4;

      } elseif (is_array($AllowableHTML)) {

# This algorithm, relies on the fact that this regexp will match a leading null
# (that is, when the first character in $Message is a < or >), so that
# $MessageParts[0] will never be '<', and all the odd-numbered indices of
# $MessageParts will contain the text within the HTML tags.

         $MessageParts        = split('<[[:space:]]*|[[:space:]]*>', $Message);
         $EditedMessage       = $MessageParts[0];
         $ProblemTags   = '';
         unset($TagStack);
         $TagStack = array();

         for ($i=1; $i < count($MessageParts); $i++) {

            if ($i%2) {    # Odd number - tag text!

               reset($AllowableHTML);
               $TagOk = 0;
               while (list( , $stuff) = each($AllowableHTML)) {
                  list($start, $end, $tagname) = $stuff;

                  if($start != '' && eregi("^$start\$", $MessageParts[$i])) {

                     $TagOk         = 1;
                     $EditedMessage .= '<'.$MessageParts[$i].'>';
                     if ($end != '') { $TagStack[] = $start; }

                  } elseif ($end != '') {

                     if(eregi("^$end\$", $MessageParts[$i])) {

                        $TagOk         = 1;
                        $EditedMessage .= '<'.$MessageParts[$i].'>';

                        $x = count($TagStack)-1;

                        if ($TagStack[$x] != $start) {
                           $TagStack[] = $end;
                        } else {
                           $TagStack[$x] = '';
# I tried to use
#     unset($TagStack[$x]);
# here, but PHP (3.0.6 under WinNT4.0sp3) died ('Document contains no data' in
# Netscape, no error logged) on unset($TagStack[0]). This means I need an extra
# step below to clean out blank elements.

                        }

                     }

                  }

                  if (!$TagOk) { $StatusCode = 3; }

               }

            } else {       # Even - plain text

               $EditedMessage .= $MessageParts[$i];

            }

         }

         if (!empty($TagStack)) {    # Clean out all blank elements in stack
            unset($Temp);
            reset($TagStack);
            while (list($i, $value) = each($TagStack)) {
               if ($value != '') { $Temp[] = $value; }
            }
            if (is_array($Temp)) {
               $StatusCode          = -3;
               $ProblemTags   = implode(',', $Temp);
            }
         }

      }

/*
      return array($StatusCode, $EditedMessage);
*/
      return $EditedMessage;
}
?>
