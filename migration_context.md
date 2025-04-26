# Context Summary: PHP Template Migration to YATT

## Goal

Migrate the PHP application entirely from the custom `Template` class (`include/template.inc`) to the existing YATT templating system (`lib/YATT/YATT.class.php`), which is already partially used via an outer wrapper (`user/page-yatt.inc.php`).

## Current State Analysis

*   **YATT:** Partially used, provides outer page structure (`page-yatt.inc.php`).
*   **`Template` Class:** (`include/template.inc`) Used extensively, especially in `user/` and `user/account/` directories.
    *   Syntax: `{VAR_NAME}`, `<!-- BEGIN BLOCK_NAME -->...<!-- END BLOCK_NAME -->`.
    *   `set_block(parent, handle, name)`: Extracts block `handle` from `parent`'s content, stores it in variable `handle`, and *replaces* the original block in `parent` with `{name}` placeholder. This necessitates explicit clearing if blocks are meant to be omitted.
    *   `set_var(name, value)`: Escapes `{`, `$`, `\` characters in `value` automatically.
    *   `parse(target, handle, append=true)`: Used for building repeated content (loops).
    *   `unknowns="comment"`: Typical setting, replaces undefined `{VAR}` with HTML comments.
*   **Inline PHP:** Some pages use `<?php echo ... ?>` or `<?= ... ?>` directly in PHP/PHTML files (especially in `admin/`).

## Key Differences Between Template and YATT

1. **Block Handling:**
   - **Template:** Requires explicit `set("")` to clear a block, otherwise it remains in the output
   - **YATT:** By default, blocks that are not parsed are not rendered in the output
   - This makes YATT more predictable but requires careful block parsing order

2. **Template Structure:**
   - **Template:** Uses HTML comments for block definitions
   - **YATT:** Uses `%begin [block_name]` and `%end [block_name]` syntax
   - YATT blocks can be nested.

3. **Variable Handling:**
   - **Template:** Uses `{VAR_NAME}` syntax
   - **YATT:** Uses `%[VAR_NAME]` syntax
   - YATT requires variables to be set before parsing blocks that use them

4. **Conditional Logic & Comments:**
   - **YATT Conditional Logic:** **Does NOT support conditional syntax like `%if` within the template file itself.** Conditional rendering is achieved by defining named blocks (`%begin [name]...%end [name]`) in the `.yatt` file and then **selectively calling `$yatt->parse('block_name')` from the PHP script** based on the desired conditions.
   - **YATT Comment Syntax:** The correct format for comments within YATT templates is **`%[#] Comment Text Here`**. Note the literal `[#]` after the percent sign and lack of trailing `]` at the very end of the line. These are distinct from standard HTML comments (`<!-- ... -->`).

5. **Page Structure:**
   - **Template:** Standalone templates
   - **YATT:** Uses an outer `page.yatt` wrapper for all pages
   - Future consideration: Migrate from current `page-yatt.inc.php` to pure YATT

5. **Parse Order:**
   - **Template:** No specific order required
   - **YATT:** Technically no order requirement, but following a top-to-bottom parse order is recommended as an idiom because:
     - Makes code more readable and maintainable
     - Follows natural template flow
     - Helps prevent variable dependency issues
     - Makes debugging easier
     - Matches the visual structure of the template

## Routing System

The application uses a routing system in `user/main.php` that maps `.phtml` URLs to actual PHP files. This affects how we test and develop:

1. **URL Structure:**
   - Pages are accessed through `.phtml` extensions
   - Example: `login.php` is accessed via `login.phtml`
   - Base URL: `https://forums-git.wayot.org/`

2. **Account-related Routes:**
   ```php
   $account_scripts = array(
       "login.phtml" => "account/login.php",
       "logout.phtml" => "account/logout.php",
       "forgotpassword.phtml" => "account/forgotpassword.php",
       "create.phtml" => "account/create.php",
       "acctedit.phtml" => "account/acctedit.php",
       "finish.phtml" => "account/finish.php",
       "f" => "account/finish.php",
   );
   ```

3. **Routing System Features:**
   - Sets up include paths
   - Handles database connections
   - Initializes templates
   - Manages user sessions
   - Checks IP bans

4. **Testing Implications:**
   - Must test through actual URLs, not local files
   - Need to consider server environment
   - Template paths must be correct for remote server
   - Session handling must work across requests
   - Cookie handling:
     - Can save cookies from curl using `-c` or `--cookie-jar`
     - Can reuse cookies in subsequent requests using `-b` or `--cookie`
     - Example: `curl -c cookies.txt https://forums-git.wayot.org/login.phtml`
     - Then: `curl -b cookies.txt https://forums-git.wayot.org/other-page.phtml`

5. **Cookie Format:**
   - Name: `KawfAccount`
   - Value: MD5 hash of "cookie" + email + microtime()
   - Duration: 5 years
   - Path: "/"
   - Domain: Set by `$cookie_host` variable
   - Stored in database: `u_users.cookie` column
   - Used for session persistence
   - Can be unset via `unsetcookie()` method

## Testing and Debugging

1. **Error Logging:**
   - Error log is symlinked to `error-git.log`
   - Timestamps in UTC/GMT
   - Server timezone is PDT
   - Example: 15:23:54 UTC = 08:23:54 PDT
   - YATT errors are logged with format: `YATT errors in [file]: [error message]`
   - PHP errors include stack traces and file locations

2. **Testing Approaches:**
   - Must test through actual URLs (forums-git.wayot.org)
   - Can use curl with cookie handling:
     - Save cookies: `curl -c cookies.txt [url]`
     - Use cookies: `curl -b cookies.txt [url]`
   - Test cases should include:
     - Initial page loads
     - Form submissions
     - Error conditions
     - Session persistence

3. **Common Error Patterns:**
   - Template not found: `FIND([block]): Could not find node [block]`
   - File not found: `Failed to open stream: No such file or directory`
   - Variable issues: `Call to a member function set() on [type]`
   - Empty content: `content_html: ''`

4. **Debugging Steps:**
   - Check error log for recent errors
   - Verify template paths and file existence
   - Confirm block names match between PHP and YATT files
   - Test variable setting and parsing order
   - Verify cookie handling and session persistence

## Migration Plan (Phased Approach)

1.  **Phase 0:** Analysis, Baseline Capture (save full HTML output from `master`)

2.  **Phase 2 In Progress:**
    *   **`user/preferences.php`**: Migrated (associated with database encoding fix). Tested. OK.
    *   **`user/tracking.php`**: Migrated. Tested OK (Normal/Simple modes, links, conditionals). Forum header handled via `generic.tpl` logic embedded in YATT (see note above).
    *   **`user/showforum.php`**: **Provisionally Migrated.** (See Pending Verification below).
    *   **`user/showmessage.php`**: Migrated. Tested OK.
    *   **`user/showthread.php`**: Migrated. Tested OK.
        *   Also required refactoring `render_message` in `message.inc` to use YATT and return HTML.
        *   Required fixing recursion/memory/type errors in `listthread.inc` and `filter.inc`.
        *   Call to `filter_messages` commented out in `showthread.php` as potentially redundant.

3.  **Pending Verification (Deferred):**
    *   **`create.php`**: Form submission logic (success/failure), error message details, Terms of Use handling.
    *   **`finish.php`**: Action processing logic for different `type` values (NewAccount, ChangeEmail, ForgotPassword success/error paths).
    *   **`user/showforum.php` Pagination Edge Cases (Page > 1):**
        *   Admin vs. User Visibility: Compare pagination consistency for pages containing deleted threads when logged in as admin vs. regular user.
        *   Show Prefs Toggling: Verify pagination remains consistent when toggling `ShowModerated` / `ShowOffTopic` for pages containing such threads.
        *   Own Posts Visibility: Check pages containing user's own posts mixed with other states (e.g., off-topic) to ensure they are counted correctly.

4.  **Next Steps:**
    *   Continue Phase 2 migrations: Target `user/post.php` next.
    *   Later: Come back to perform Pending Verification tests.
    *   Later: Revisit `user/tracking.php` forum header implementation when addressing the branch with forum-specific headers.
    *   Later: Establish more formal testing framework if needed.

## Current Step & Next Action (Current Session)

*   **Previous:** User committed changes after showthread/showmessage migration and YATT plan creation.
*   **Current:** Proceed with migrating `user/post.php`.

## Migration: `user/post.php`

This script handles both displaying the form for posting new messages/replies and processing the submitted form data.

**Refactoring Steps:**

1.  Converted the original `templates/post.tpl` to `templates/post.yatt`, defining relevant blocks (`post_content`, `header`, `disabled`, `error`, `preview`, `duplicate`, `form`, `accept`).
2.  Refactored `user/post.php` significantly:
    *   Replaced old template instantiation and parsing with YATT object creation and `set`/`parse` calls.
    *   Moved form rendering logic into a dedicated function `render_postform()` within `user/postform.inc`.
    *   Moved core message processing and database insertion/update logic into a dedicated function `postmessage()` within `user/postmessage.inc`.
    *   Consolidated and cleaned up `require_once` statements at the top of `user/post.php`.

**Issues Encountered & Resolutions:**

*   **Preview Button Text:**
    *   Initially showed "Update Message" after preview due to `mid` being passed unintentionally. Fixed by `unset($nmsg['mid'])` before calling `render_postform` in the preview state (`user/post.php`).
    *   Later showed "Post Followup" for new threads due to using `!isset($msg['pmid'])` in `render_postform`. Fixed by changing the check to `empty($msg['pmid'])` in `user/postform.inc`. A comment was added explaining this.
*   **Fatal Errors During Posting:**
    *   `find_msg_duplicates()`: This function call was added erroneously during refactoring and never existed in the original codebase. The call block was removed from `user/post.php`.
    *   `post_message()`: The call used an underscore, while the actual function defined in `user/postmessage.inc` was `postmessage` (no underscore). The call in `user/post.php` was corrected.
    *   `ArgumentCountError` for `postmessage()`: The call was missing the required `$request` (`$_POST`) argument. The call in `user/post.php` was corrected to include all four arguments (`$user`, `$fid`, `$msg`, `$_POST`).
*   **New Thread Association:** New threads were initially associated with `tid=0` because the `postmessage()` function used `!isset($msg['pmid'])` to detect new threads, which failed when `pmid=0` was submitted. This was corrected by changing the condition to `if (empty($msg['pmid']))` in `user/postmessage.inc`. A comment was added explaining this necessity.
*   **Success Page Link:** The "Go to Your Message" link pointed to `mid=1` because `$msg['mid']` was being overwritten by the boolean return value of `postmessage()`. Removed the incorrect assignment (`$msg['mid'] = $mid;`) in `user/post.php`.
*   **Duplicate Notification Page:** The page displayed only a sparse warning. Updated the `duplicate` block in `templates/post.yatt` to include the message preview and navigation links (similar to the `accept` block) and added an informational header "Content updated to:" for clarity.
*   **Include Management:** Consolidated multiple `require_once` blocks, removed redundant includes, and corrected paths/presence based on testing (e.g., ensuring `user/postmessage.inc` was included, restoring the `user/` prefix for `image.inc`).

**Current Status:**

`user/post.php` and its associated template/includes appear fully migrated and functional, handling posting, previewing, replies, new threads, duplicate detection, and error display correctly using the YATT system.
