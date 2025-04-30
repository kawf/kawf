# Page Context Migration Plan

## Current State

The codebase currently uses two global variables for page context:
- `$page_context`: Set in main.php as `$script_name . $path_info`
- `$_page`: Set in main.php from `$_REQUEST['page']`

### Current Usage Patterns

1. Form Submissions:
```php
// In postform.inc
$current_page_for_form = isset($_page) ? $_page : ($script_name . $path_info);
$hidden .= hidden("page", $current_page_for_form);
$form_tpl->set("PAGE", $current_page_for_form);
```

2. Action Links:
```php
// In printsubject.inc
$page = $page_context;
return append_tools($user, $string, $forum, $thread, $msg, $page);
```

3. Redirects:
```php
// In various action files
header("Location: " . $_page);
```

### Current Issues

1. Inconsistent variable usage (`$page_context` vs `$_page`)
2. Global variables make testing and maintenance difficult
3. No centralized handling of page context
4. Mix of URL parameters and template variables
5. No clear fallback strategy

## Migration Plan

### 1. Add New Functions to util.inc

```php
function get_page_context() {
    return $_REQUEST['page'] ?? '';
}

function set_page_context($context) {
    return "page=" . urlencode($context);
}

function get_return_url() {
    $context = get_page_context();
    return $context ? set_page_context($context) : '';
}
```

### 2. Migration Steps

1. Replace Global Variables:
   - Remove `$page_context` global
   - Remove `$_page` global
   - Update all references to use new functions

2. Update Form Handling:
   - Replace form hidden field generation
   - Update template variable setting
   - Maintain backward compatibility

3. Update Action Links:
   - Modify `append_tools()` to use new functions
   - Update all action URLs
   - Keep existing URL structure

4. Update Redirects:
   - Replace direct `$_page` usage
   - Use new functions for redirect URLs
   - Maintain existing behavior

### 3. Files to Modify

```
user/
├── main.php                    # Remove globals
├── postform.inc               # Update form handling
├── printsubject.inc           # Update link generation
├── showforum.php              # Update page context usage
├── showthread.php             # Update page context usage
├── showmessage.php            # Update page context usage
├── tracking.php               # Update page context usage
├── post.php                   # Update form handling
├── edit.php                   # Update form handling
├── preferences.php            # Update form handling
├── delete.php                 # Update redirects
├── undelete.php               # Update redirects
├── lock.php                   # Update redirects
├── unlock.php                 # Update redirects
├── track.php                  # Update redirects
├── untrack.php                # Update redirects
├── sticky.php                 # Update redirects
├── markuptodate.php           # Update redirects
└── account/
    ├── login.php              # Update form handling
    ├── create.php             # Update form handling
    ├── forgotpassword.php     # Update form handling
    └── acctedit.php           # Update form handling
```

### 4. Testing Strategy

1. Unit Tests:
   - Test new functions in isolation
   - Verify URL generation
   - Check fallback behavior

2. Integration Tests:
   - Test form submissions
   - Test action links
   - Test redirects
   - Verify template rendering

3. Manual Testing:
   - Test all form submissions
   - Test all action links
   - Test all redirects
   - Verify browser history behavior

### 5. Rollback Plan

1. Keep old code commented out initially
2. Add feature flags if needed
3. Maintain backward compatibility
4. Document all changes

## Benefits

1. Centralized page context handling
2. Removed global variables
3. Consistent behavior across codebase
4. Easier testing and maintenance
5. Clear fallback strategy
6. Maintained backward compatibility

## Risks

1. Complex migration due to widespread usage
2. Potential for missed edge cases
3. Need to maintain backward compatibility
4. Template system dependencies

## Timeline

1. Add new functions to util.inc
2. Update one file at a time
3. Test each change
4. Deploy incrementally
5. Monitor for issues
6. Remove old code

## Future Improvements

1. Consider session-based approach
2. Add caching if needed
3. Improve error handling
4. Add logging
5. Consider URL structure improvements
