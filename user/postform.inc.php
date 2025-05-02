<?php
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
 * @param bool $imgpreview Optional flag for image preview state.
 * @return string Rendered HTML for the form.
 */
function render_postform($template_dir, $action, $user, $msg = null, $imgpreview = false)
{
    global $thread, $forum, $script_name, $path_info;
    // global $tthreads_by_tid; // Not used directly here?
    global $Debug;

    // Instantiate YATT for the form template
    try {
        $form_tpl = new_yatt('postform.yatt', $forum);
    } catch (Exception $e) {
        error_log("Failed to instantiate YATT for postform.yatt: " . $e->getMessage());
        return "<p class=\"error\">Error loading post form template.</p>";
    }

    // --- Determine form state flags ---
    $is_form_enabled = true;
    $show_locked_msg = false;
    $show_noreplies_msg = false;
    $show_nonewthreads_msg = false;
    $is_acct_active = false;
    $show_offtopic_option = false;
    $show_image_upload = false;

    // Check conditions that disable the form
    if (isset($thread) && !isset($forum['option']['PostReply']) && !$user->capable($forum['fid'], 'Delete')) {
        $is_form_enabled = false;
        $show_noreplies_msg = true;
    } else if (!isset($thread) && !isset($forum['option']['PostThread']) && !$user->capable($forum['fid'], 'Delete')) {
        $is_form_enabled = false;
        $show_nonewthreads_msg = true;
    } else if (isset($thread) && isset($thread['flag']['Locked']) && !$user->capable($forum['fid'], 'Lock')) {
        $is_form_enabled = false;
        $show_locked_msg = true;
    }

    // Set flags on the form template
    $form_tpl->set('is_form_enabled', $is_form_enabled);
    $form_tpl->set('show_locked_msg', $show_locked_msg);
    $form_tpl->set('show_noreplies_msg', $show_noreplies_msg);
    $form_tpl->set('show_nonewthreads_msg', $show_nonewthreads_msg);

    // Set page context variables - needed in both enabled and disabled states
    $form_tpl->set("PAGE_VALUE", get_page_context());
    $form_tpl->set("PAGE", format_page_param());

    // --- Debug Info (Optional) ---
    $debug = "\naction = $action\n";
    $debug .= "imgpreview = $imgpreview\n";
    $debug .= "_REQUEST:\n";
    foreach ($_REQUEST as $k => $v) {
      if (!is_numeric($k)) {
        if(is_scalar($v))
          $debug.=" $k => $v\n";
        else {
          trigger_error("$k's v is not a scalar");
          if (is_array($v)) foreach ($v as $kk => $vv)
            trigger_error("$kk => $vv");
          $_REQUEST[$k]="I like cock";
        }
      }
    }

    if ($Debug) {
      if (isset($msg)) {
        $debug .= "msg:\n";
        foreach ($msg as $k => $v) {
          if (!is_numeric($k) && is_scalar($v))
            $debug.=" $k => $v\n";
        }
      }
      $debug = str_replace("--","- -", $debug);
      $form_tpl->set("POSTFORM_DEBUG", "<!-- $debug -->");
    } else {
      $form_tpl->set("POSTFORM_DEBUG", "");
    }

    // --- Set variables based on login status and form state ---
    if ($is_form_enabled && $user->valid()) { // Check $user->valid() instead of isset($user->aid)
        $is_acct_active = true;

        // Set Hidden Fields
        if (!isset($_REQUEST['postcookie'])) $postcookie = md5("post" . microtime());
        else $postcookie = $_REQUEST['postcookie'];
        $hidden = hidden("postcookie", $postcookie);
        $hidden .= hidden("forumname", $forum['shortname']);
        // Use get_page_context() for form hidden fields
        $hidden .= hidden("page", get_page_context());
        if ($imgpreview) $hidden .= hidden("imgpreview", 'true');
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
        if (isset($_REQUEST['preview']) || isset($_REQUEST['post'])) {
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
        $show_offtopic_option = isset($forum['option']['OffTopic']);
        if ($offtopic && !$user->capable($forum['fid'], 'OffTopic')) {
             // User cannot unset offtopic if already set and no perms
             $show_offtopic_option = false;
        }
        if (!$track_thread) $email_followup = false; // Can't follow if not tracking
        $show_image_upload = can_upload_images() && !isset($msg["mid"]);

        $form_tpl->set('show_offtopic_option', $show_offtopic_option);
        $form_tpl->set('show_image_upload', $show_image_upload);
        $form_tpl->set("OFFTOPIC_CHECKED", $offtopic ? " checked" : "");
        $form_tpl->set("EXPOSEEMAIL_CHECKED", $expose_email ? " checked" : "");
        $form_tpl->set("EMAILFOLLOWUP_CHECKED", $email_followup ? " checked" : "");
        $form_tpl->set("TRACKTHREAD_CHECKED", $track_thread ? " checked" : "");
        $form_tpl->set("MAXIMAGEFILEBYTES", max_image_upload_bytes());

    } else {
         // Not enabled or not logged in - $is_acct_active remains false
         $is_acct_active = false;
    }

    $form_tpl->set('is_acct_active', $is_acct_active);

    // --- Explicitly parse blocks within postform.yatt ---
    if (!$is_form_enabled) {
        // Parse the relevant disabled message block
        if($show_nonewthreads_msg) $form_tpl->parse('post_form_content.disabled.nonewthreads');
        if($show_noreplies_msg) $form_tpl->parse('post_form_content.disabled.noreplies');
        if($show_locked_msg) $form_tpl->parse('post_form_content.disabled.locked');
        $form_tpl->parse('post_form_content.disabled');
    } else {
        if (!$is_acct_active) {
            $form_tpl->parse('post_form_content.enabled.noacct');
        } else {
            // Parse nested blocks for logged-in user
            if ($show_image_upload) $form_tpl->parse('post_form_content.enabled.acct.imageupload');
            if ($show_offtopic_option) $form_tpl->parse('post_form_content.enabled.acct.offtopic');
            $form_tpl->parse('post_form_content.enabled.acct');
        }
        $form_tpl->parse('post_form_content.enabled');
    }

    // Return the fully rendered HTML
    try {
        return $form_tpl->output('post_form_content');
    } catch (Exception $e) {
        error_log("YATT output error in render_postform: " . $e->getMessage());
        return "<p class=\"error\">Error rendering post form.</p>";
    }
}
// vim: sw=2
?>
