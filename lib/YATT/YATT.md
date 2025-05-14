# YATT Development Guide

## Core YATT Concepts

### 1. Template Structure
- Templates use `.yatt` extension
- Directives must be on their own lines
- Block syntax: `%begin [block_name]` and `%end [block_name]`
- Variable syntax: `%[VAR_NAME]`
- Comments: `%[#] Comment text here` (must be on its own line)

### 2. Key Differences from Other Template Systems

#### No In-Template Conditionals
- YATT does NOT support `%if` or similar conditional syntax in templates
- Instead, use named blocks and selective parsing:
  ```php
  // In PHP:
  $content_block = "preview"; // or "accept" or "duplicate"
  $yatt->parse("post_content.$content_block");
  ```

#### Variable Handling
- Variables must be set before parsing blocks that use them
- No `get()` method exists - handle variable retrieval in PHP code
- Unset variables trigger errors (this is intentional)
- Do NOT set empty defaults - let errors surface template issues
- Variables are set individually:
  ```php
  $yatt->set("VAR_NAME", "value");
  // Or as an array:
  $yatt->set([
      "VAR1" => "value1",
      "VAR2" => "value2"
  ]);
  ```

#### Block Parsing
- Unparsed blocks are not rendered (no need to clear them)
- Blocks can be nested
- Parse order should follow top-to-bottom template flow
- Blocks can be parsed multiple times for repeated content

### 3. Common Idioms

#### Page Structure
```php
// Outer page wrapper
$page = new YATT("page.yatt");
$content = new YATT("content.yatt");

// Set page-level variables
$page->set("title", "Page Title");
$page->set("content", $content->output());
$page->parse("main");
```

#### Form Handling
```php
// In PHP:
$form = new YATT("form.yatt");
$form->set("action", "/submit");
$form->set("method", "POST");

// Parse different form sections based on state
if ($is_edit) {
    $form->parse("edit_fields");
} else {
    $form->parse("create_fields");
}
```

#### List Rendering
```php
// In PHP:
$list = new YATT("list.yatt");
foreach ($items as $item) {
    $list->set("item_name", $item['name']);
    $list->set("item_id", $item['id']);
    $list->parse("item");
}
```

### 4. Best Practices

#### HTML Escaping
- Do NOT use `htmlspecialchars()` on fields that go through `stripcrap()`
  - Applies to: subject, message, url, urltext, video fields
- Use `htmlspecialchars()` for other fields (name, email, etc.)

#### Error Handling
- Use YATT's built-in error callback system
- Let unset variable errors surface to catch template issues
- Log template errors with context:
  ```php
  $yatt->setErrorCallback(function($msg) {
      error_log("YATT error in " . $yatt->getTemplate() . ": " . $msg);
  });
  ```

#### Parse Order
1. Set all variables first
2. Parse blocks in top-to-bottom order
3. Parse nested blocks after their parents
4. Parse repeated content last

#### Template Organization
- Keep templates focused and single-purpose
- Use nested templates for complex pages
- Name blocks descriptively
- Group related blocks together
- Use comments to document template structure

### 5. Common Pitfalls

1. **Directive Placement**
   - ❌ Wrong: `%begin block` on same line as content
   - ✅ Correct: `%begin block` on its own line

2. **Variable Setting**
   - ❌ Wrong: Setting variables after parsing blocks
   - ✅ Correct: Set all variables before parsing

3. **Block Parsing**
   - ❌ Wrong: Relying on block clearing
   - ✅ Correct: Use selective parsing

4. **Error Handling**
   - ❌ Wrong: Setting empty defaults for variables
   - ✅ Correct: Let errors surface template issues

### 6. Testing

1. **Template Testing**
   - Test all block combinations
   - Verify variable handling
   - Check error conditions
   - Test nested templates

2. **Integration Testing**
   - Test through actual URLs
   - Verify session handling
   - Check cookie persistence
   - Test form submissions

3. **Error Testing**
   - Test missing variables
   - Test invalid block names
   - Test malformed templates
   - Verify error logging
