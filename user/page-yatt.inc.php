<?php
require_once("lib/YATT/YATT.class.php");

function generate_page($title, $contents)
{
    global $tpl, $template_dir, $domain;

    $page = new YATT();
    $page->load("$template_dir/page.yatt");
    $page->set('domain', $domain);
    $page->set('css_href', css_href());
    $page->set('title', $title);
    $page->set('contents', $contents);
    $page->parse('page');

    return $page->output();
}
