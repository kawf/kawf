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

*   **Previous:** Migrated `user/showmessage.php`, `user/showthread.php`, and associated includes (`message.inc`, `listthread.inc`, `filter.inc`). Tested OK.
*   **Current:** User to commit changes. Then proceed with migrating `user/post.php`.
