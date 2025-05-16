<?php
// Common functionality for post.php and edit.php

function handle_image_upload($user, $msg, $forum, $error, $content_tpl) {
    $upload_config = get_upload_config();
    if (empty($error) && can_upload_images($upload_config) && !empty($_POST['fileData']) && !empty($_POST['fileMetadata'])) {
        // Get filename information from the hidden input
        $fileMetadata = json_decode($_POST['fileMetadata'], true);

        // Create a temporary file from the data URL
        $tempFile = tempnam(sys_get_temp_dir(), 'kawf_');
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['fileData']));
        file_put_contents($tempFile, $data);

        // Rename the temp file to use the correct filename from metadata
        $finalTempFile = dirname($tempFile) . '/' . $fileMetadata['resized'];
        rename($tempFile, $finalTempFile);

        // Create upload context
        $context = create_upload_context(
            $upload_config,
            $finalTempFile,
            $fileMetadata,
            $user->aid,
            $forum['fid']
        );

        // Get the image URLs
        $result = upload_image($context);
        if (isset($result['error'])) {
            $error["image_upload_failed"] = true;
            $content_tpl->set("UPLOAD_ERROR", $result['error']);
        } else {
            $msg["imageurl"] = $result['url'];
            $msg["imagedeleteurl"] = $result['delete_url'];
            if (isset($result['metadata_path'])) {
                $msg["metadatapath"] = $result['metadata_path'];
            }
        }
    }
    return $msg;
}

function validate_message($msg, $error, $parent = null) {
    // Subject validation
    if (empty($msg['subject'])) {
        $error["subject_req"] = true;
        $msg['subject'] = '...'; // Default subject if empty
    } elseif (isset($parent) && $msg['subject'] == "Re: " . $parent['subject'] && empty($msg['message']) && empty($msg['url'])) {
        $error["subject_change"] = true; // Discourage empty "Re:" posts
    }
    if (mb_strlen($msg['subject']) > 100) {
        $error["subject_too_long"] = true;
        $msg['subject'] = mb_strcut($msg['subject'], 0, 100);
    }

    // URL length checks
    $max_item_len = 250;
    foreach (['url', 'urltext', 'imageurl', 'video'] as $item) {
        if (isset($msg[$item]) && mb_strlen($msg[$item]) > $max_item_len) {
            $error[$item . '_too_long'] = true;
            $msg[$item] = mb_strcut($msg[$item], 0, $max_item_len);
        }
    }
}

// Handle preview state -- returns tuple of (show_preview, seen_preview)
function handle_preview_state($msg, $error, $show_preview, $seen_preview) {
    // $show_preview: Controls whether to show the preview block in the UI
    // $seen_preview: Tracks whether the user has seen the image/video preview

    // If there's an image or video but the user hasn't seen it yet ($seen_preview=false),
    // force them to see a preview by setting $show_preview=true
    if ((!empty($msg['imageurl']) || !empty($msg['video'])) && !$seen_preview) {
        $show_preview = true;
    }

    // If there are validation errors or we're showing a preview,
    // ensure the image preview state is properly set
    if ((!empty($error) || $show_preview)) {
        $seen_preview = true;  // User has acknowledged seeing the image/video preview
        // Set flags to show image/video in the preview form
        if(!empty($msg['imageurl'])) $error["image"] = true;
        if(!empty($msg['video'])) $error["video"] = true;
    }

    return [$show_preview, $seen_preview];
}

function calculate_message_flags($user, $msg) {
    $flagset = ["NewStyle"]; // Base flag
    if (empty($msg['message'])) $flagset[] = "NoText";
    if (!empty($msg['url']) || preg_match("/<[[:space:]]*a[[:space:]]+href/i", $msg['message'])) $flagset[] = "Link";
    if (!empty($msg['video']) || preg_match("/<[[:space:]]*video[[:space:]]+src/i", $msg['message'])) $flagset[] = "Video";
    if (!empty($msg['imageurl']) || preg_match("/<[[:space:]]*img[[:space:]]+src/i", $msg['message'])) $flagset[] = "Picture";
    return implode(",", $flagset);
}

function calculate_message_diff($user, $old_msg, $new_msg) {
    $diff = '';

    // State changes
    if ($old_msg['state'] != $new_msg['state']) {
        $diff .= "Changed from '".$old_msg['state']."' to '".$new_msg['state']."'\n";
    }

    // Email changes
    if (empty($old_msg['email']) && !empty($new_msg['email']))
        $diff .= "Exposed e-mail address\n";
    else if (!empty($old_msg['email']) && empty($new_msg['email']))
        $diff .= "Hid e-mail address\n";

    // Email notification changes
    if (isset($_POST['EmailFollowup']) && !is_msg_etracked($old_msg))
        $diff .= "Requested e-mail notification\n";
    else if (!isset($_POST['EmailFollowup']) && is_msg_etracked($old_msg))
        $diff .= "Cancelled e-mail notification\n";

    // Thread tracking changes
    if (isset($_POST['TrackThread']) && !is_msg_tracked($old_msg))
        $diff .= "Tracked message\n";
    else if (!isset($_POST['TrackThread']) && is_msg_tracked($old_msg))
        $diff .= "Untracked message\n";

    // Content changes
    $old = ["Subject: " . $old_msg['subject']];
    $old = array_merge($old, explode("\n", $old_msg['message']));
    if (!empty($old_msg['url'])) {
        $old[] = "urltext: " . $old_msg['urltext'];
        $old[] = "url: " . $old_msg['url'];
    }
    if (!empty($old_msg['imageurl']))
        $old[] = "imageurl: " . $old_msg['imageurl'];
    if (!empty($old_msg['video']))
        $old[] = "video: " . $old_msg['video'];

    $new = ["Subject: " . $new_msg['subject']];
    $new = array_merge($new, explode("\n", $new_msg['message']));
    if (!empty($new_msg['url'])) {
        $new[] = "urltext: " . $new_msg['urltext'];
        $new[] = "url: " . $new_msg['url'];
    }
    if (!empty($new_msg['imageurl']))
        $new[] = "imageurl: " . $new_msg['imageurl'];
    if (!empty($new_msg['video']))
        $new[] = "video: " . $new_msg['video'];

    $diff .= diff($old, $new);
    return $diff;
}
// vim: ts=8 ts=4 et
