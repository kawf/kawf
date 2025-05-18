<?php
// give a good path, so we can include this from admin and account
require_once("lib/YATT/YATT.class.php");
require_once("notices.inc.php");
require_once("image.inc.php");

/**
 * Default error handler for YATT templates
 * @param array $errors Array of error messages
 * @param YATT $yatt The YATT instance
 * @param array $context Additional context information
 */
function default_yatt_error_handler($errors, $yatt, $context) {
    // Get the caller's caller since this is a callback.
    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $bt[1];
    $hdr = $caller['file'] . ":" . $caller['line'];

    // Log to debug log
    debug_log($hdr . "\n" . $yatt->format_errors());

    // Log to error log
    error_log($hdr . ": " . $yatt->format_errors());
}

function new_yatt($template, $forum = null) {
    global $template_dir;
    $yatt = new YATT($template_dir, $template);

    // Set default error handler with context
    $yatt->setErrorCallback('default_yatt_error_handler', [
        'template' => $template,
        'forum' => $forum ? $forum['shortname'] : null
    ]);

    if ($forum) {
        $yatt->set('FORUM_NAME', htmlspecialchars($forum['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $yatt->set('FORUM_SHORTNAME', htmlspecialchars($forum['shortname'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $yatt->set('FORUM_NOTICES', isset($forum['notices']) ? $forum['notices'] : '');
    }
    return $yatt;
}

function generate_forum_header($forum) {
    global $template_dir;

    // Try to load forum-specific template first
    $forum_specific = 'forum/' . $forum['shortname'] . '.yatt';
    if (file_exists($template_dir . '/' . $forum_specific)) {
	$forum_template = new_yatt($forum_specific, $forum);
    } else {
	$forum_template = new_yatt('forum/generic.yatt', $forum);
    }

    // Parse the forum header content
    $forum_template->parse('forum_header');
    return $forum_template->output();
}

function generate_page($title, $contents, $skip_header=false, $meta_robots=false)
{
    global $template_dir, $domain, $Debug;

    // Try to get forum context, but don't require it
    try {
        $forum = get_forum();
    } catch (Exception $e) {
        $forum = null;
    }

    $page = new_yatt('page.yatt');
    $page->set('domain', $domain);
    $page->set('css_href', css_href());
    $page->set('skin_css_href', skin_css_href());
    $page->set('js_href', js_href());
    $page->set('js_jquery_href', js_href($filename="jquery-3.7.1.slim.min.js", $cache_buster=false));
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

    // Set user variables if user is valid
    global $user, $config_paypal, $config_hosting;
    if (isset($config_paypal['hosted_button_id'])) {
        $page->set('RETURN_VALUE', full_url($_SERVER));
        $page->set('BUTTON_ID', $config_paypal['hosted_button_id']);
        if ($user->valid()) {
            $page->set('USER_EMAIL', $user->email);
            $page->set('USER_NAME', $user->name);
            $page->set('USER_AID', $user->aid);
            $page->parse('page.paypal.user');
        }
        $page->parse('page.paypal');
    }
    if (isset($config_hosting['url']) && isset($config_hosting['text'])) {
        $page->set('TEXT', $config_hosting['text']);
        $page->set('URL', $config_hosting['url']);
        $page->parse('page.hosting');
    }

    if (!$skip_header) {
        $page->set('PAGE_PARAM', format_page_param());
        // Set forum navigation
        $nav = get_forum_navigation();

        // Add forums by category
        $first_category = true;
        foreach ($nav as $category => $forums) {
            if (!empty($forums)) {
                if (!$first_category) {
                    $page->set('shortname', '');
                    $page->set('name', ' ─ ');
                    $page->parse('page.header.forums.category');
                }
                foreach ($forums as $shortname => $name) {
                    $page->set('shortname', $shortname);
                    $page->set('name', $name);
                    $page->parse('page.header.forums.category');
                }
                $first_category = false;
            }
        }

        $page->parse('page.header.forums');
        if (can_upload_images()) {
            $page->parse('page.header.images');
        }
        $page->parse('page.header');

        // Handle forum header if we're in a forum context
        if ($forum !== null) {
            // Set the header content in the main page template
            $page->set('forum_header_content', generate_forum_header($forum));

            // Parse the forum header block
            $page->parse('page.forum_header');
        }
    }

    if ($Debug && get_debug_log() != "") {
        $page->set('debug_contents', "<pre>\n" . get_debug_log() . "</pre>\n");
        $page->parse('page.debug_log');
    }

    $page->parse('page');
    return trim($page->output());
}

// Set up the YATT-based error renderer
set_error_page_renderer(function($error_data) {
    global $template_dir;
    $error_tpl = new YATT($template_dir, '404.yatt');

    // Set template variables
    $error_tpl->set(array(
        "DESCRIPTION" => $error_data['description'],
        "URI" => $error_data['uri'],
        "SERVER_SOFTWARE" => $error_data['server_info']['software'],
        "SERVER_NAME" => $error_data['server_info']['name'],
        "SERVER_PORT" => $error_data['server_info']['port']
    ));

    // Parse and return the rendered page
    $error_tpl->parse("error_content");
    $content_html = $error_tpl->output("error_content");

    return generate_page($error_data['title'], $content_html);
});
// vim: sw=4 ts=8 et
?>
