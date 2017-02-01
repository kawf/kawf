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
    $oneurl = $default_page;
  }
  $url = $oneurl;
}

if (isset($_REQUEST['refresh']) ) {
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta http-equiv="refresh" content="0; URL=<?php echo $url; ?>">
	<title></title>
</head>
<body><p><a href="<?php echo $url; ?>">Link</a></p></body>
</html>
<?php
} else {
    header("Location: $url");
}
?>
