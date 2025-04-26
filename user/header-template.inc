<?php

/**
 * Renders the dynamic forum header using YATT.
 *
 * Checks for a forum-specific .yatt file and falls back to generic.yatt.
 *
 * @param array $forum Associative array containing forum details (must include 'shortname' and 'name').
 * @param string $template_dir Base directory where templates are located.
 * @return string The rendered HTML for the forum header, or an empty string on failure.
 */
function render_forum_header_yatt($forum, $template_dir)
{
    // Define relative paths
    $specific_header_yatt_rel = "forum/" . $forum['shortname'] . ".yatt";
    $generic_header_yatt_rel = "forum/generic.yatt";

    // Define absolute paths
    $specific_header_yatt_abs = $template_dir . "/" . $specific_header_yatt_rel;
    $generic_header_yatt_abs = $template_dir . "/" . $generic_header_yatt_rel;

    // Choose the correct absolute path
    $chosen_header_yatt_abs = file_exists($specific_header_yatt_abs) ? $specific_header_yatt_abs : $generic_header_yatt_abs;

    $forum_header_html = ''; // Default to empty

    if (file_exists($chosen_header_yatt_abs)) {
        try {
            // Read the content of the chosen .yatt file
            $content = file_get_contents($chosen_header_yatt_abs);
            if ($content === false) {
                 error_log("Failed to read forum header template: $chosen_header_yatt_abs");
                 return '';
            }

            // Manually replace placeholders - crude but avoids nested YATT issues
            // Note: This assumes the snippet ONLY contains the content for the header
            // and doesn't rely on complex block parsing within the snippet itself.
            $replacements = array(
                '%[FORUM_NAME]' => htmlspecialchars($forum['name']),
                '%[FORUM_SHORTNAME]' => htmlspecialchars($forum['shortname'])
                // Add other replacements if the header snippets use more vars
            );

            // Remove block tags manually if they exist, as we just want the inner content
            $content = preg_replace('/%begin \[forum_header_content\]\s*/', '', $content);
            $content = preg_replace('/\s*%end \[forum_header_content\]\s*/', '', $content);

            $forum_header_html = str_replace(array_keys($replacements), array_values($replacements), $content);

        } catch (Exception $e) {
            error_log("Exception reading/processing forum header ($chosen_header_yatt_abs): " . $e->getMessage());
            return ''; // Return empty on error
        }
    } else {
        // Log an error if even the generic fallback wasn't found
        error_log("Could not find forum header template (checked specific and generic): $chosen_header_yatt_abs");
    }

    return $forum_header_html;
}

// Add other user-specific template helpers here in the future...

?>
