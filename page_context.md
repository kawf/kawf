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
├── postform.inc               ✅ Updated to use both PAGE_VALUE and PAGE
├── delete.php                 ✅ Updated to use get_page_context(false) for Location
├── undelete.php              ✅ Updated to use get_page_context(false) for Location
│                              ⚠️ Special case: Uses raw page value for changestate
├── preferences.php           ✅ Updated to use both variables
├── showmessage.php           ✅ Updated to use both variables
├── printsubject.inc          ✅ Updated to use format_page_param() for links
├── lock.php                  ✅ Updated to use get_page_context(false) for Location
├── unlock.php                ✅ Updated to use get_page_context(false) for Location
├── track.php                 ✅ Updated to use get_page_context(false) for Location
├── sticky.php                ✅ Updated to use get_page_context(false) for Location
├── markuptodate.php          ✅ Updated to use get_page_context(false) for Location
├── gmessage.php              ✅ Updated to use get_page_context(false) for Location
└── account/
    ├── login.php             ✅ Updated to use PAGE_VALUE
    ├── logout.php            ✅ Updated to use get_page_context(false) for Location
    └── forgotpassword.php    ✅ Updated to use PAGE_VALUE
```

### Templates Updated

```
templates/
├── postform.yatt             ✅ Updated to use PAGE for links
├── delete.yatt              ✅ Updated to use PAGE_VALUE for form
├── undelete.yatt            ✅ Updated to use PAGE_VALUE for form
├── preferences.yatt         ✅ Updated to use PAGE for links
├── showmessage.yatt         ✅ Updated to use both variables
└── account/
    ├── login.yatt           ✅ Updated to use PAGE_VALUE for form
    ├── create.yatt          ✅ Updated to use PAGE_VALUE for form
    └── forgotpassword.yatt  ✅ Updated to use PAGE_VALUE for form
```

### Benefits
- Clear distinction between use cases
- Consistent behavior
- Improved security
- Maintained compatibility

### Known Issues
1. **URL Validation:**
   - Need to add validation for page context values
   - Consider adding URL sanitization
   - Review security implications of page context values

### Migration Status
- [x] Core functions added
- [x] Basic usage updated
- [x] Review multi-parameter redirects
- [ ] Add URL validation
- [ ] Standardize hardcoded paths
