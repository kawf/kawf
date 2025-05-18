<?php

require_once("image.inc.php"); // For can_upload_images, get_upload_config, max_image_upload_bytes

function hidden($name, $value)
{
  return "<input type=\"hidden\" name=\"" . htmlspecialchars($name) . "\" "
  	. "value=\"" . htmlspecialchars($value) . "\">\n";
}

/**
 * Generates the HTML for the post/edit form using postform.yatt
 *
 * @param string $template_dir Base directory for templates.
 * @param string $action The form action target (e.g., 'post', 'edit').
 * @param ForumUser $user The current user object.
 * @param array|null $msg Optional message array (for editing).
 * @param bool $seen_preview Optional flag for image preview state.
 * @return string Rendered HTML for the form.
 */
function render_postform($template_dir, $action, $user, $msg = null, $seen_preview = false)
{
    global $Debug;
    global $thread;
    $forum = get_forum();

    // Instantiate YATT for the form template
    try {
        $form_tpl = new_yatt('postform.yatt', $forum);
    } catch (Exception $e) {
        error_log("Failed to instantiate YATT for postform.yatt: " . $e->getMessage());
        return "<p class=\"error\">Error loading post form template.</p>";
    }

    // --- Determine form state flags ---

    $flags = [
      'acct_active' => false,
      'form_enabled' => false,
      'locked' => false,
      'noreplies' => false,
      'nonewthreads' => false,
      'offtopic' => false,
      'image_upload' => false,
      'expose_email' => false,
      'email_followup' => false,
      'track_thread' => false,
    ];

    // Check conditions that disable the form
    if (isset($thread) && !isset($forum['option']['PostReply']) && !$user->capable($forum['fid'], 'Delete')) {
        $flags['noreplies'] = true;
    } else if (!isset($thread) && !isset($forum['option']['PostThread']) && !$user->capable($forum['fid'], 'Delete')) {
        $flags['nonewthreads'] = true;
    } else if (isset($thread) && isset($thread['flag']['Locked']) && !$user->capable($forum['fid'], 'Lock')) {
        $flags['locked'] = true;
    } else {
        // all false, so form is enabled
        $flags['form_enabled'] = true;
    }

    // Set page context variables - needed in both enabled and disabled states
    $form_tpl->set("PAGE_VALUE", get_page_context());
    $form_tpl->set("PAGE", format_page_param());

    if ($Debug) {
      // --- Debug Info (Optional) ---
      $debug = "\naction = $action\n";
      $debug .= "seen_preview = $seen_preview\n";
      $debug .= "_REQUEST:\n";
      foreach ($_REQUEST as $k => $v) {
        if (!is_numeric($k)) {
          if(is_scalar($v))
            $debug.=" $k => " . htmlspecialchars($v, ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . "\n";
          else {
            $debug.=" $k => " . htmlspecialchars(print_r($v, true), ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . "\n";
          }
        }
      }

      if (isset($msg)) {
        $debug .= "msg:\n";
        foreach ($msg as $k => $v) {
          if (!is_numeric($k) && is_scalar($v))
            $debug.=" $k => " . htmlspecialchars($v, ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . "\n";
        }
      }
      $form_tpl->set("DEBUG_POSTFORM", "<!-- $debug -->");
    } else {
      $form_tpl->set("DEBUG_POSTFORM", "");
    }

    // --- Set variables based on login status and form state ---
    if ($flags['form_enabled'] && $user->valid()) { // Check $user->valid() instead of isset($user->aid)
        $flags['acct_active'] = true;

        // Set Hidden Fields
        if (!isset($_REQUEST['postcookie'])) $postcookie = md5("post" . microtime());
        else $postcookie = $_REQUEST['postcookie'];
        $hidden = hidden("postcookie", $postcookie);
        $hidden .= hidden("forumname", $forum['shortname']);
        // Use get_page_context() for form hidden fields
        $hidden .= hidden("page", get_page_context());
        if ($seen_preview) $hidden .= hidden("seen_preview", 'true');
        if (isset($msg['mid'])) {
             $hidden .= hidden("mid", $msg['mid']);
            $form_tpl->set("SUBMITTEXT", "Update Message");
        } else {
            // Must use empty() here for button text. A new thread passes pmid=0 (as string "0").
            // !isset($msg['pmid']) would be false, showing "Post Reply" incorrectly.
            // empty() correctly evaluates "0" as true, showing "Post New Thread".
            if (empty($msg['pmid'])) $form_tpl->set("SUBMITTEXT", "Post New Thread"); // Correctly handles pmid=0
            else $form_tpl->set("SUBMITTEXT", "Post Reply");
        }
        if (isset($msg['pmid'])) $hidden .= hidden("pmid", $msg['pmid']);
        if (isset($msg['tid'])) $hidden .= hidden("tid", $msg['tid']);
        $form_tpl->set("HIDDEN", $hidden);

        // Set Message Content Fields
        $form_tpl->set("MESSAGE", isset($msg['message']) ? $msg['message'] : '');
        $form_tpl->set("SUBJECT", isset($msg['subject']) ? escape_form($msg['subject']) : '');
        $form_tpl->set("URLLINK", isset($msg['url']) ? escape_form_url($msg['url']) : '');
        $form_tpl->set("URLTEXT", isset($msg['urltext']) ? escape_form($msg['urltext']) : '');
        $form_tpl->set("VIDEO", isset($msg['video']) ? escape_form_url($msg['video']) : '');
        $form_tpl->set("IMAGEURL", isset($msg['imageurl']) ? escape_form_url($msg['imageurl']) : '');

        // Set User Details & Context
        $form_tpl->set("USER_NAME", $user->name);
        $form_tpl->set("USER_EMAIL", $user->email);
        // Use get_page_context() for form fields that need raw value
        $form_tpl->set("PAGE_VALUE", get_page_context());
        // Use format_page_param() for navigation links that need page=value format
        $form_tpl->set("PAGE", format_page_param());
        $form_tpl->set("FORUM_SHORTNAME", $forum['shortname']); // Needed for form action
        $form_tpl->set("ACTION", $action);
        $form_tpl->set("token", $user->token());

        // Determine Checkbox States
        if (isset($_REQUEST['show_preview']) || isset($_REQUEST['post'])) {
            $offtopic = isset($_REQUEST['OffTopic']);
            $expose_email = isset($_REQUEST['ExposeEmail']);
            $email_followup = isset($_REQUEST['EmailFollowup']);
            $track_thread = isset($_REQUEST['TrackThread']);
        } else if (isset($msg['mid'])) {
            $offtopic = ($msg['state']=="OffTopic");
            $expose_email = !empty($msg['email']);
            $email_followup = is_msg_etracked($msg);
            $track_thread = is_msg_tracked($msg);
        } else {
            $offtopic = false;
            $expose_email = !isset($user->pref['SecretEmail']);
            $email_followup = false;
            $track_thread = is_thread_tracked($thread) || isset($user->pref['AutoTrack']);
        }

        // Apply Overrides and Set Flags/Vars
        $flags['offtopic'] = isset($forum['option']['OffTopic']);
        if ($offtopic && !$user->capable($forum['fid'], 'OffTopic')) {
             // User cannot unset offtopic if already set and no perms
             $flags['offtopic'] = false;
        }
        if (!$track_thread) $email_followup = false; // Can't follow if not tracking
        $upload_config = get_upload_config();
        $flags['image_upload'] = can_upload_images($upload_config);

        $form_tpl->set("OFFTOPIC_CHECKED", $offtopic ? " checked" : "");
        $form_tpl->set("EXPOSEEMAIL_CHECKED", $expose_email ? " checked" : "");
        $form_tpl->set("EMAILFOLLOWUP_CHECKED", $email_followup ? " checked" : "");
        $form_tpl->set("TRACKTHREAD_CHECKED", $track_thread ? " checked" : "");
        $form_tpl->set("MAXIMAGEFILEBYTES", max_image_upload_bytes($upload_config));
        $form_tpl->set("IMAGEDELETEURL", isset($msg['imagedeleteurl']) ? $msg['imagedeleteurl'] : '');
        $form_tpl->set("METADATAPATH", isset($msg['metadatapath']) ? $msg['metadatapath'] : '');

    } else {
         // Not enabled or not logged in - $flags['acct_active'] remains false
         $flags['acct_active'] = false;
    }

    // --- Explicitly parse blocks within postform.yatt ---
    if (!$flags['form_enabled']) {
        // Parse the relevant disabled message block
        if($flags['nonewthreads']) $form_tpl->parse('post_form_content.disabled.nonewthreads');
        if($flags['noreplies']) $form_tpl->parse('post_form_content.disabled.noreplies');
        if($flags['locked']) $form_tpl->parse('post_form_content.disabled.locked');
        $form_tpl->parse('post_form_content.disabled');
    } else {
        if (!$flags['acct_active']) {
            $form_tpl->parse('post_form_content.enabled.noacct');
        } else {
            if ($flags['image_upload']) {
              $form_tpl->set("js_image_resizer", js_href('image-resizer.js'));
              $form_tpl->set("js_postform_upload", js_href('postform.js'));
              $form_tpl->parse('post_form_content.enabled.acct.imageupload');
            }
            if ($flags['offtopic']) $form_tpl->parse('post_form_content.enabled.acct.offtopic');
            $form_tpl->parse('post_form_content.enabled.acct');
        }
        $form_tpl->parse('post_form_content.enabled');
    }

    return $form_tpl->output('post_form_content');
}
// vim: sw=2 ts=8 et
?>
