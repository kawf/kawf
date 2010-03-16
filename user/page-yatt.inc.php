<?php
require_once("lib/YATT/YATT.class.php");

function generate_page($title, $contents, $skip_header=false)
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

    /* needed for kawf donation -> aid correlation */
    if ($user->valid()) {
	$page->set('aid', $user->aid);
	$page->parse('page.user');
    }

    if (!$skip_header)
      $page->parse('page.header');
    $page->parse('page');

    return trim($page->output());
}
// vim: sw=2
?>
