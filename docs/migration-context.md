# Context Summary: PHP Template Migration to YATT and Page Context Refactoring

## Goal

1. **Template Migration Goal:**
   Migrate the PHP application entirely from the custom `Template` class (`include/template.inc`) to the existing YATT templating system (`lib/YATT/YATT.class.php`), which is already partially used via an outer wrapper (`user/page-yatt.inc.php`).

2. **Page Context Refactoring Goal:**
   Replace the current global page context system with a centralized, maintainable solution that eliminates global variables and provides consistent context handling across the application.

## Historical YATT State Analysis

*   **YATT:** Partially used, provides outer page structure (`page-yatt.inc.php`).
*   **`Template` Class:** (`include/template.inc`) Used extensively, especially in `user/` and `user/account/` directories.
    *   Syntax: `{VAR_NAME}`, `<!-- BEGIN BLOCK_NAME -->...<!-- END BLOCK_NAME -->`.
    *   `set_block(parent, handle, name)`: Extracts block `handle` from `parent`'s content, stores it in variable `handle`, and *replaces* the original block in `parent` with `{name}` placeholder. This necessitates explicit clearing if blocks are meant to be omitted.
    *   `set_var(name, value)`: Escapes `{`, `$`, `\` characters in `value` automatically.
    *   `parse(target, handle, append=true)`: Used for building repeated content (loops).
    *   `unknowns="comment"`: Typical setting, replaces undefined `{VAR}` with HTML comments.

## Key Differences Between Template and YATT

1. **Block Handling:**
   - **Template:** Uses `set("")` to clear a block, otherwise it remains in the output
   - **YATT:** No need to clear blocks - unparsed blocks are not rendered
   - This makes YATT more predictable but requires careful block parsing order
   - YATT supports using a state variable to determine which block to parse
   - Example: `$content_block = "preview"` or `"accept"` or `"duplicate"`
   - Parse the chosen block: `$content_tpl->parse("post_content.$content_block")`
   - This is cleaner than multiple conditional parse statements

2. **Template Structure:**
   - **Template:** Uses HTML comments for block definitions
   - **YATT:** Uses `%begin [block_name]` and `%end [block_name]` syntax on their own lines.
   - YATT blocks can be nested.

3. **Variable Handling:**
   - **Template:** Uses `{VAR_NAME}` syntax
   - **YATT:** Uses `%[VAR_NAME]` syntax
   - YATT requires variables to be set before parsing blocks that use them
   - YATT's `set()` method:
     - Can take a single variable name and value: `$yatt->set("VAR_NAME", "value")`
     - Can also accept an array of variables like Template's `set_var()`
     - Variables must be set individually before parsing blocks that use them
     - No need to clear variables - unset variables are simply not rendered
   - **Important:** YATT does NOT have a `get()` method. Any logic that previously used `get()` to retrieve variables should be handled in PHP code instead.
   - **Template:** Unset variables are silently ignored
   - **YATT:** Unset variables trigger errors - DO NOT set empty defaults. This is a feature, not a bug, as it helps catch template errors where variables are accidentally used without being set.
   - **HTML Escaping:**
     - Do NOT use htmlspecialchars() on message fields that go through stripcrap()
     - This applies specifically to: subject, message, url, urltext, video fields
     - Other fields like name, email still need htmlspecialchars() as they don't use the HTML tag validation system

4. **Error Handling:**
   - **Template:** No built-in error handling
   - **YATT:** Uses YATT's built-in error callback system

5. **Conditional Logic & Comments:**
   - **YATT Conditional Logic:** **Does NOT support conditional syntax like `%if` within the template file itself.** Conditional rendering is achieved by defining named blocks (`%begin [name]...%end [name]`) in the `.yatt` file and then **selectively calling `$yatt->parse('block_name')` from the PHP script** based on the desired conditions.
   - **YATT Comment Syntax:** The correct format for comments within YATT templates is **`%[#] Comment Text Here`** (must be on its own line). These are distinct from standard HTML comments (`<!-- ... -->`).

6. **Page Structure:**
   - **Template:** Standalone templates
   - **YATT:** Uses an outer `page.yatt` wrapper for all pages via `generate_page()` (defined in `page-yatt.inc.php`).

7. **Parse Order:**
   - **Template:** No specific order required
   - **YATT:** Technically no order requirement, but following a top-to-bottom parse order is recommended as an idiom because:
     - Makes code more readable and maintainable
     - Follows natural template flow
     - Helps prevent variable dependency issues
     - Makes debugging easier
     - Matches the visual structure of the template

## Current YATT State Analysis

*   **Inline PHP:** Some pages may still use `<?php echo ... ?>` or `<?= ... ?>` directly in PHP/PHTML files (especially in `admin/`).

## Routing System

The application uses a routing system in `user/main.php` that maps `.phtml` URLs to actual PHP files. This affects how we test and develop:

1. **URL Structure:**
   - Pages are accessed through `.phtml` extensions
   - Example: `login.php` is accessed via `login.phtml`

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
   - Error log is symlinked to `error.log`
   - Timestamps in UTC/GMT
   - Server timezone is PDT
   - Example: 15:23:54 UTC = 08:23:54 PDT
   - YATT errors are logged with format: `YATT errors in [file]: [error message]`
   - PHP errors include stack traces and file locations

2. **Testing Approaches:**
   - Must test through actual URLs
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

1.  **Phase 1:** Analysis, Baseline Capture. ✅ COMPLETED

2.  **Phase 2 Completed:** ✅
    *   **Admin Section:** Migrated (`admin.php`, `forumadd.php`, `forummodify.php`, `gmessage.php` use YATT). Others deferred/skipped.
    *   **User Section (Core):** Verified Migrated/Using YATT (All core pages like `tracking.php`, `showmessage.php`, `post.php`, `edit.php`, `preferences.php`, `showforum.php`, `plainmessage.php`, etc.).
    *   **User Section (Helpers/Includes):** Verified Migrated/Using YATT (`util.inc` (`err_not_found`), `message.inc`, `postform.inc`, etc.).
    *   **User Section (Utilities):** Verified (`track.php`, and by extension `untrack.php`, `changestate.php`, `lock.php`, `sticky.php`) - These do not use templates.
    *   **Account Section (`user/account/`):** Verified Migrated/Using YATT/No Template Needed (`login.php`, `logout.php`, `forgotpassword.php`, `create.php`, `acctedit.php`, `finish.php`). `tou.tpl` dependency confirmed non-blocking.
    *   **Tools:** `tools/offtopic.php` dependency on `template.inc` removed (template was missing anyway).
    *   **Core (`user/main.php`):** Refactored to remove `template.inc` dependency and global `$tpl` object.
    *   **Core (`user/printsubject.inc`):** Refactored to use temporary global `$page_context` instead of `$tpl`.
    *   **Cleanup:** `include/template.inc` deleted. All associated `.tpl` files deleted (except restored/converted samples).
    *   **Analysis Complete:** Identified global variables `$page_context` and `$_page` usage patterns
    *   **Initial Changes:**
        - Added new context functions to `util.inc`
        - Created test cases for context usage
        - Documented current patterns

3.  **Current Phase: Clean Up**
    *   ✅ **Rename `.inc` include files to `.inc.php**
        - `git mv` all of them
        - Fix all references
    *   **From [Issue #40](https://github.com/kawf/kawf/issues/40):** Replace globals in `include/utils.inc` with `user/kawfGlobals.class.php` object
        *   **Implementation Plan:**
            - Create `kawfGlobals` class structure
            - Migrate global variables from `utils.inc`
            - Update all references to use new class
            - Add proper encapsulation and access methods
        *   **Benefits:**
            - Better code organization
            - Improved testability
            - Reduced global state
            - Clearer data flow
        *   **Integration:**
            - Coordinate with page context changes
            - Update template system usage
            - Modify asset handling
            - Update testing framework

4.  **Pending Verification (Deferred):**
    *   Remaining admin scripts migration (if desired)
    *   Final integration testing

## YATT Library Updates & Testing (Recent Session)

*   **Bug Fix:** Modified `YATT::load` to enforce strict line-by-line parsing for directives (`%begin`/`%end`), resolving issues where directives were sometimes treated as literal text.
*   **Bug Fix:** Modified `YATT::load` to correctly merge consecutive text lines into single nodes in the parse tree, restoring behavior closer to original intent and resolving potential layout issues.
*   **Testing Improvements:**
    *   Refactored `test_immediate_nesting.php` to use generic names and less verbose output.
    *   Added `test_multi_parse.php` to verify accumulation of parsed blocks before output.
    *   Added `test_text_merge.php` to verify correct text node handling.
    *   Added `test_comments.php` to verify comment processing.

### Known Issues
1. **Testing:**
   - URL validation coverage

2. **Page Context Escaping:**
   - ⚠️ TODO: Review usage of htmlspecialchars() with page context values
   - Current documentation suggests always using htmlspecialchars()
   - But most code doesn't use it with get_page_context()
   - Need to investigate security implications
   - May need to update documentation or add escaping

3. **Location Headers:**
   - Need to investigate security implications
   - May need to update documentation or add escaping
