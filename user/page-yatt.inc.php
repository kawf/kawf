<?php
require_once("lib/YATT/YATT.class.php");

function generate_page($title, $contents)
{
    global $template_dir, $domain;
    global $user;

    $page = new YATT($template_dir, 'page.yatt');
    $page->set('domain', $domain);
    $page->set('css_href', css_href());
    $bch = browser_css_href();
    if ($bch) {
	$page->set('browser_css_href', $bch);
	$page->parse('page.bch');
    }
    $page->set('browser_css_href', browser_css_href());
    $page->set('title', $title);
    $page->set('contents', $contents);

    if ($user->valid()) {
	$page->set('aid', $user->aid);
	$page->parse('page.user');
    }
    $page->parse('page');

    return trim($page->output());
}
// vim: sw=2
?>
