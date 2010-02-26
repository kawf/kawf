<?php
$url = stripslashes($_REQUEST['url']);
if (empty($url)) $url = $_SERVER['HTTP_REFERER'];

if(is_array($url)) {
  // Redirect to the first non-empty value in the array.
  $oneurl = "";
  foreach($url as $oneurl) {
    if($oneurl) {
      break;
    }
  }
  if(!$oneurl) {
    $oneurl = "/tracking.phtml";
  }
  header("Location: $oneurl");
} else {
  header("Location: $url");
}
?>
