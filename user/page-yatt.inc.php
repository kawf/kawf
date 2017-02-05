<?php
require_once("lib/YATT/YATT.class.php");

function generate_page($title, $contents, $skip_header=false, $meta_robots=false)
{
    global $template_dir, $domain;

    $page = new YATT($template_dir, 'page.yatt');
    $page->set('domain', $domain);
    $page->set('css_href', css_href());
    $page->set('js_href', js_href());
    $page->set('js_jquery_href', js_href($filename="jquery-1.11.1.min.js", $cache_buster=false));
    $bch = browser_css_href();
    if ($bch) {
	$page->set('browser_css_href', $bch);
	$page->parse('page.bch');
    }
    $page->set('browser_css_href', browser_css_href());
    if($meta_robots) {
        $page->set('robots', $meta_robots);
        $page->parse('page.meta_robots');
    }
    $page->set('title', $title);
    $page->set('contents', $contents);
    if (!$skip_header)
      $page->parse('page.header');
    $page->parse('page');

    $ret = trim($page->output());

    /* Workaround for issue #38 - default mysql collation is latin1_swedish,
       which means single byte characters, some of which are not utf8. */
    /* Fortunately, all kawf pages go through this interface now, so we
       we have an opportunity to fix it here */
    if (is_valid_utf8($ret))
	return $ret;

    /* contains non-UTF8 - try to convert it */
    return mb_convert_encoding($ret, 'UTF-8', 'ASCII,ISO-8859-1,8bit');
}
// vim: sw=2
?>
