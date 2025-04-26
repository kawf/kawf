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
   - YATT blocks can be nested and must be parsed in the correct order

3. **Variable Handling:**
   - **Template:** Uses `{VAR_NAME}` syntax
   - **YATT:** Uses `%[VAR_NAME]` syntax
   - YATT requires variables to be set before parsing blocks that use them

4. **Page Structure:**
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

1.  **Phase 0:** Analysis, Baseline Capture (save full HTML output from `master` for key pages/states).
2.  **Phase 1:** Migrate simple pages (minimal loops/`set_block`). **(Completed)**
3.  **Phase 2:** Migrate pages with loops (`parse(..., true)`) and conditionals (`set_block`). **(In Progress)**
4.  **Phase 3:** Migrate complex/mixed-usage pages (`user/showtracking.php`).
5.  **Phase 4:** Migrate pages using inline PHP/PHTML.
6.  **Phase 5:** Cleanup (remove `template.inc`, old `.tpl` files, `generate_page()`).

## Migration Strategy Details & Learnings

*   **Templates:** Convert `.tpl` files used by `Template` to `.yatt` files.
    *   Syntax: `{VAR}` -> `%[VAR]`, `<!-- BEGIN/END -->` -> `%begin/end []`.
    *   Structure: Define blocks statically (including nested blocks for `set_block` logic).
*   **PHP Refactoring (Current Pattern):**
    *   Keep `require_once("page-yatt.inc.php")`.
    *   Create a *new* YATT instance (`$content_tpl`) for the page-specific `.yatt` template.
    *   Replace `Template` method calls (`set_file`, `set_block`, `set_var`) with YATT equivalents (`new YATT`, `$content_tpl->set`) or remove if obsolete (`set_block`).
    *   **Preserve original logic structure** as much as possible for cleaner diffs.
    *   Add YATT parsing logic (`$content_tpl->parse(...)`) within the correct conditional branches based on original state variables (`$error`, `$success`, etc.).
    *   Generate final page content: `$content_html = $content_tpl->output();`.
    *   **Keep final call** `print generate_page('Page Title', $content_html);` for now.
    *   Check YATT errors: `if ($content_errors = $content_tpl->get_errors()) { error_log(...) }`.
*   **Handle Escaping Difference:** Manually escape data in PHP *before* `$content_tpl->set()` if the old `Template` escaping was required (not encountered yet).
*   **YATT Library:** No changes to `YATT.class.php` itself should be needed.
*   **`unknowns`:** Accept YATT's default behavior (remove undefined vars + log error), rather than replicating `Template`'s "comment" behavior.
*   **`user/tracking.php` Notes:** The migration handled the dynamic forum header loading by replicating the structure of `templates/forum/generic.tpl` within `templates/tracking.yatt`. A separate branch exists with more complex, forum-specific header templates (`templates/forum/*.tpl`) which will require revisiting this implementation later.

## Current Progress

1.  **Phase 1 Completed:**
    *   **`user/account/login.php`**: Migrated. Tested login flow (initial load, submit, cookie). OK.
    *   **`user/account/forgotpassword.php`**: Migrated. Tested flows (initial load, known/unknown email submit). OK.
    *   **`user/account/acctedit.php`**: Migrated. Tested flows (load, name/pw/email change). OK.
    *   **`user/account/create.php`**: Migrated. **Rendering/basic errors tested OK.**
    *   **`user/account/finish.php`**: Migrated. **Rendering/basic errors tested OK.**
    *   Established migration pattern (see above).
    *   Added `.gitattributes` to enforce Unix line endings.
    *   Fixed line ending issues in YATT templates.
    *   Test credentials:
        *   Email: nyet@nyet.org
        *   Password: [redacted]

2.  **Phase 2 In Progress:**
    *   **`user/preferences.php`**: Migrated (associated with database encoding fix). Tested. OK.
    *   **`user/tracking.php`**: Migrated. Basic rendering tested OK. Forum header handled via `generic.tpl` logic embedded in YATT (see note above).

3.  **Pending Verification (Deferred):**
    *   **`create.php`**: Form submission logic (success/failure), error message details, Terms of Use handling.
    *   **`finish.php`**: Action processing logic for different `type` values (NewAccount, ChangeEmail, ForgotPassword success/error paths).

4.  **Next Steps:**
    *   Continue Phase 2 migrations: Target `user/showforum.php` next.
    *   Later: Come back to perform Pending Verification tests.
    *   Later: Revisit `user/tracking.php` forum header implementation when addressing the branch with forum-specific headers.
    *   Later: Establish more formal testing framework if needed.

## Tool Status

*   The `edit_file` tool **is available** and functional in this session.

## Current Step & Next Action (Current Session)

*   Proceed with migrating `user/showforum.php`.
