# Page Context Migration Plan

## Current State

The codebase uses several standardized patterns for page context:
- `PAGE_VALUE`: Raw page context value for use in forms
- `PAGE`: Formatted URL parameter string (with `page=` prefix) for links
- Direct `$page` variable: Used for Location headers and some direct URL handling

Recent Standardizations:
- Location headers now use `get_page_context(false)` directly
- Special case in undelete.php: Uses raw page value to prevent fallback behavior

### Standardized Usage Patterns

1. Form Hidden Fields:
```php
// In templates
<input type="hidden" name="page" value="%[PAGE_VALUE]">

// In PHP files
$form_tpl->set("PAGE_VALUE", get_page_context());
```

2. Navigation Links:
```php
// In templates
<a href="somepage.phtml?%[PAGE]">Link Text</a>

// In PHP files
$content_tpl->set("PAGE", format_page_param());
```

3. Redirects (Location Headers):
```php
// Simple redirects
header("Location: " . get_page_context(false));  // No fallback for Location headers

// Multi-parameter redirects (Standard)
header("Location: somepage.phtml?state=Active&mid=$mid&" . format_page_param() . "&token=$stoken");

// Multi-parameter redirects (Special Case - No Fallback)
header("Location: changestate.phtml?state=Active&mid=$mid&page=$page&token=$stoken");
```

### Core Functions

1. `get_page_context($use_fallback = true)`: Returns the raw page context value
   - Used for form hidden fields
   - Used for template variables that need the raw value
   - Optional fallback to current URL if no page parameter is present
   - Should use `false` for Location headers to prevent unwanted fallbacks
   - Special case: Some multi-parameter URLs need raw value to prevent fallback

2. `format_page_param()`: Returns the formatted URL parameter string
   - Used for generating URL parameters in links and forms
   - Adds "page=" prefix and URL encodes the value
   - Returns empty string if no page context exists
   - Should NOT be used for Location headers
   - Used in multi-parameter URLs when fallback is acceptable

### Important Notes

1. Location Headers:
   - Should use `get_page_context(false)` to prevent fallbacks
   - Critical for maintaining user navigation state
   - Multi-parameter URLs need special consideration:
     - Use `format_page_param()` when fallback is acceptable
     - Use raw page value when fallback must be prevented

2. Form Values:
   - Use `get_page_context()` with fallback for forms
   - Fallback behavior is appropriate for form submissions
   - TODO: Review if htmlspecialchars() is needed with page context values
   - Current codebase generally doesn't use htmlspecialchars() with get_page_context()

3. Link Parameters:
   - Use `format_page_param()` for generating URL parameters
   - Should not be used for Location headers
   - Consider fallback behavior requirements

### Files Updated

```
user/
├── changestate.php          ✅ Updated to use get_page_context(false) for Location
├── delete.php               ✅ Updated to use get_page_context(false) for Location
├── lock.php                 ✅ Updated to use get_page_context(false) for Location
├── gmessage.php             ✅ Updated to use get_page_context(false) for Location
├── markuptodate.php         ✅ Updated to use get_page_context(false) for Location
├── postform.inc.php         ✅ Updated to use both PAGE_VALUE and PAGE
├── preferences.php          ✅ Updated to use both variables
├── printsubject.inc.php     ✅ Updated to use format_page_param() for links
├── showmessage.php          ✅ Updated to use both variables
├── sticky.php               ✅ Updated to use get_page_context(false) for Location
├── track.php                ✅ Updated to use get_page_context(false) for Location
├── undelete.php             ✅ Updated to use get_page_context(false) for Location
├── unlock.php               ✅ Updated to use get_page_context(false) for Location
└── account/
    ├── acctedit.php         ✅ Updated to use PAGE_VALUE for form
    ├── create.php           ✅ Updated to use PAGE_VALUE for form
    ├── forgotpassword.php   ✅ Updated to use PAGE_VALUE for form
    ├── login.php            ⚠️ Special handling: uses a combination of get_page_context(false) and _REQUEST['url'] for PAGE_VALUE and Location:
    └── logout.php           ⚠️ Special handling: uses format_page_param() for Location: page= parameter
```

### Templates Updated

```
templates/
├── 404.yatt                 ✅ No page context usage
├── delete.yatt              ✅ Uses PAGE_VALUE for form
├── edit.yatt                ✅ No page context usage
├── footer.yatt              ✅ No page context usage
├── header.yatt              ✅ No page context usage
├── message.yatt             ✅ Uses PAGE for links
├── page.yatt                ✅ No page context usage
├── post.yatt                ✅ Uses PAGE for links
├── postform.yatt            ✅ Uses both PAGE for links and PAGE_VALUE for form
├── preferences.yatt         ✅ Uses both PAGE for links and PAGE_VALUE for form
├── showmessage.yatt         ✅ Uses PAGE for links
├── showthread.yatt          ✅ Uses PAGE for links
├── showtracking.yatt        ✅ Uses PAGE for links
├── showforum.yatt           ✅ Uses PAGE for links
├── tracking.yatt            ✅ Uses PAGE for links
├── undelete.yatt            ✅ Uses PAGE_VALUE for form
├── account/
│   ├── create.yatt          ✅ Uses PAGE_VALUE for form
│   ├── edit.yatt            ✅ Uses PAGE_VALUE for form
│   ├── forgotpassword.yatt  ✅ Uses PAGE_VALUE for form
│   └── login.yatt           ✅ Uses PAGE_VALUE for form
└── admin/
    ├── admin.yatt           ✅ No page context usage
    ├── admin_page.yatt      ✅ No page context usage
    ├── forumadd.yatt        ✅ No page context usage
    ├── forummodify.yatt     ✅ No page context usage
    └── gmessage.yatt        ✅ No page context usage
```
