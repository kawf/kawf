<?php
if (!isset($_REQUEST['url']) || (is_string($_REQUEST['url']) && $_REQUEST['url'] === '')) {
    $candidate = (isset($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
} elseif (is_string($_REQUEST['url'])) {
    $candidate = $_REQUEST['url'];
} else {
    $candidate = '';
}

$external = safe_external_url($candidate);
if ($external !== '') {
    $url = $external;
} elseif ($candidate !== '' && safe_local_url($candidate) === $candidate) {
    $url = $candidate;
} elseif (isset($default_page) && is_string($default_page) && $default_page !== '') {
    $url = $default_page;
} else {
    $url = '/';
}

if (isset($_REQUEST['refresh']) ) {
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta http-equiv="refresh" content="0; URL=<?php echo h($url); ?>">
	<title></title>
</head>
<body><p><a href="<?php echo h($url); ?>">Link</a></p></body>
</html>
<?php
} else {
    header("Location: $url");
}
?>
