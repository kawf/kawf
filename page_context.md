# Page Context Migration Plan

## Current State

The codebase uses several standardized patterns for page context:
- `PAGE_VALUE`: Raw page context value for use in forms
- `PAGE`: Formatted URL parameter string (with `page=` prefix) for links
- Direct `$page` variable: Used for Location headers and some direct URL handling

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
$page = get_page_context(false);  // No fallback for Location headers
header("Location: $page");

// Conditional redirects
if (isset($_REQUEST['page'])) {
    header("Location: " . get_page_context(false));
} else {
    header("Location: /");  // Explicit fallback
}

// Multi-parameter redirects
header("Location: somepage.phtml?state=Active&mid=$mid&page=$page&token=$stoken");
// Note: Still using $page directly in URL construction - needs review
```

4. Hardcoded Hrefs:
```php
// In templates
<a href="somepage.phtml?page=%[PAGE_VALUE]">Link Text</a>
// or
<a href="somepage.phtml?%[PAGE]">Link Text</a>
```

### Core Functions

1. `get_page_context($use_fallback = true)`: Returns the raw page context value
   - Used for form hidden fields
   - Used for template variables that need the raw value
   - Optional fallback to current URL if no page parameter is present
   - Should use `false` for Location headers to prevent unwanted fallbacks

2. `format_page_param()`: Returns the formatted URL parameter string
   - Used for generating URL parameters in links and forms
   - Adds "page=" prefix and URL encodes the value
   - Returns empty string if no page context exists
   - Should NOT be used for Location headers

### Important Notes

1. Location Headers:
   - Should use `get_page_context(false)` to prevent fallbacks
   - Critical for maintaining user navigation state
   - Multi-parameter URLs still need review for proper page parameter handling

2. Form Values:
   - Use `get_page_context()` with fallback for forms
   - Fallback behavior is appropriate for form submissions

3. Link Parameters:
   - Use `format_page_param()` for generating URL parameters
   - Should not be used for Location headers

4. Hardcoded Hrefs:
   - Need to decide between PAGE and PAGE_VALUE based on context
   - May need standardization

### Files Updated

```
user/
├── postform.inc               # Updated to use both PAGE_VALUE and PAGE
├── delete.php                 # Updated to use get_page_context(false) for Location
├── undelete.php              # Updated to use get_page_context(false) for Location
├── preferences.php           # Updated to use both variables
├── showmessage.php           # Updated to use both variables
├── printsubject.inc          # Updated to use format_page_param() for links
├── lock.php                  # Updated to use get_page_context(false) for Location
├── unlock.php                # Updated to use get_page_context(false) for Location
├── track.php                 # Updated to use get_page_context(false) for Location
├── sticky.php                # Updated to use get_page_context(false) for Location
├── markuptodate.php          # Updated to use get_page_context(false) for Location
├── gmessage.php              # Updated to use get_page_context(false) for Location
└── account/
    ├── login.php             # Updated to use PAGE_VALUE
    ├── logout.php            # Updated to use get_page_context(false) for Location
    └── forgotpassword.php    # Updated to use PAGE_VALUE
```

### Templates Updated

```
templates/
├── postform.yatt             # Updated to use PAGE for links
├── delete.yatt              # Updated to use PAGE_VALUE for form
├── undelete.yatt            # Updated to use PAGE_VALUE for form
├── preferences.yatt         # Updated to use PAGE for links
├── showmessage.yatt         # Updated to use both variables
└── account/
    ├── login.yatt           # Updated to use PAGE_VALUE for form
    ├── create.yatt          # Updated to use PAGE_VALUE for form
    └── forgotpassword.yatt  # Updated to use PAGE_VALUE for form
```

### Benefits

1. Clear distinction between different page context use cases
2. Consistent behavior across codebase
3. Easier testing and maintenance
4. No unwanted fallbacks in critical paths
5. Maintained backward compatibility

### Testing Strategy

1. Form Submissions:
   - Test all forms with hidden page fields
   - Verify correct page context is maintained
   - Check behavior when no page parameter

2. Navigation Links:
   - Test all links using PAGE variable
   - Verify correct URL formatting
   - Check proper URL encoding

3. Redirects:
   - Test all Location headers
   - Verify correct page value is used
   - Verify no unwanted fallbacks
   - Test multi-parameter redirects

### Future Improvements

1. Review multi-parameter redirects for proper page parameter handling
2. Consider creating dedicated function for Location headers
3. Reconsider fallback behavior in get_page_context()
4. Standardize hardcoded href usage
5. Add unit tests for page context functions
6. Add logging for debugging page context issues
7. Consider session-based approach for complex flows
8. Add caching if needed
9. Improve error handling
10. Consider URL structure improvements

### Migration Status

- [x] Add new functions to util.inc
- [x] Update form handling
- [x] Update navigation links
- [x] Update simple redirects
- [x] Update conditional redirects
- [ ] Review multi-parameter redirects
- [ ] Add unit tests
- [ ] Add logging
- [ ] Update documentation
- [ ] Monitor for issues
- [ ] Standardize hardcoded href usage
- [ ] Reconsider fallback behavior
