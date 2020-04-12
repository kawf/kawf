<?php
require_once("lib/YATT/YATT.class.php");

function generate_page($title, $contents, $skip_header=false, $meta_robots=false)
{
    global $template_dir, $domain, $Debug;

    $page = new YATT($template_dir, 'page.yatt');
    $page->set('domain', $domain);
    $page->set('css_href', css_href());
    $page->set('skin_css_href', skin_css_href());
    $page->set('js_href', js_href());
    $page->set('js_jquery_href', js_href($filename="jquery-3.5.0.slim.min.js", $cache_buster=false));
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
    if ($Debug) {
	$page->set('debug_contents', "<pre>\n" . get_debug_log() . "</pre>\n");
	$page->parse('page.debug_log');
    }
    if (!$skip_header)
      $page->parse('page.header');
    $page->parse('page');

    return trim($page->output());
}
// vim: sw=2
?>
