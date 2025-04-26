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
2.  **Phase 1:** Migrate simple pages (minimal loops/`set_block`).
3.  **Phase 2:** Migrate pages with loops (`parse(..., true)`) and conditionals (`set_block`).
4.  **Phase 3:** Migrate complex/mixed-usage pages (`user/showtracking.php`).
5.  **Phase 4:** Migrate pages using inline PHP/PHTML.
6.  **Phase 5:** Cleanup (remove `template.inc`, old `.tpl` files, `generate_page()`).

## Migration Strategy Details

*   **Templates:** Convert `.tpl` files used by `Template` to `.yatt` files.
    *   Syntax: `{VAR}` -> `%[VAR]`, `<!-- BEGIN/END -->` -> `%begin/end []`.
    *   Structure: Define blocks statically (including nested blocks for `set_block` logic).
*   **PHP Refactoring:**
    *   Integrate fully with outer `$page` YATT object from `page-yatt.inc.php`.
    *   Create a *new* YATT instance (`$content_tpl`) for the page-specific `.yatt` template.
    *   Replace `new Template()`, `set_file`, `set_block`, `set_var`, `parse` calls.
    *   Use `$content_tpl->set(...)` for variables. **Handle escaping difference:** Manually escape data in PHP *before* `set()` if the old `Template` escaping was required.
    *   Use PHP `if/else` logic to call `$content_tpl->parse('path.to.block')` for conditional sections (replaces `set_block` logic).
    *   Use PHP `foreach/while` loops calling `$content_tpl->parse('path.to.row.block')` inside (replaces `parse(..., append=true)`).
    *   Get final content HTML via `$content_html = $content_tpl->output();`. Check `$content_tpl->get_errors()`.
    *   Pass final content and title to the outer wrapper: `$page->set('page_content', $content_html); $page->set('page_title', ...);`.
    *   Remove calls to `generate_page()`.
*   **YATT Library:** No changes to `YATT.class.php` itself should be needed.
*   **`unknowns`:** Accept YATT's default behavior (remove undefined vars + log error), rather than replicating `Template`'s "comment" behavior.

## Current Progress

1. **Completed:**
   - Migrated `user/account/login.php` and its template
   - Migrated `user/account/forgotpassword.php` and its template
   - Migrated `user/account/acctedit.php` and its template
   - Established patterns for block parsing and variable handling
   - Implemented error checking via `get_errors()`
   - Tested login and forgot password flows:
     - Login page loads and renders correctly
     - Login form submits and sets cookie
     - Forgot password link works
     - Forgot password form handles both known and unknown emails
   - Tested account edit functionality:
     - Form loads correctly
     - Name changes work
     - Password changes work
     - Email changes work
   - Added `.gitattributes` to enforce Unix line endings
   - Fixed line ending issues in YATT templates
   - Test credentials:
     - Email: nyet@nyet.org
     - Password: [redacted]

2. **In Progress:**
   - Testing and verifying migrated pages
   - Documenting common patterns and best practices
   - Planning next phase of migrations

3. **Next Steps:**
   - Continue with Phase 1 migrations:
     - `create.php` (account creation)
     - `finish.php` (account setup completion)
   - Establish testing framework for migrated pages
   - Document any edge cases or special handling needed

## Tool Status (Previous Session)

*   The `edit_file` tool was **not available** in the previous SSH remote session. File modifications required providing code for manual copy-paste.

## Current Step & Next Action (Current Session)

1. **In Progress:** Migrating `user/account/create.php`
   * Created new YATT template `templates/account/create.yatt`
   * Refactored PHP file to use YATT templating
   * Key changes:
     - Replaced Template class with YATT instance
     - Converted template syntax ({VAR} -> %[VAR], <!-- BEGIN/END --> -> %begin/%end)
     - Removed generate_page() in favor of $page->set()
     - Added error checking via get_errors()

2. **Next Steps:**
   * Apply the provided code changes
   * Run test cases:
     1. Initial page load
     2. Form display
     3. Error handling
   * Compare output with baseline files in tests/baseline/
   * Check PHP error logs for any YATT errors
   * Report results of comparison and any issues found

3. **Pending Verification:**
   * Form submission handling
   * Error message display
   * Terms of Use agreement handling
