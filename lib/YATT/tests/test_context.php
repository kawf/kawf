<?php
require_once __DIR__ . '/../YATT.class.php';

class TestCase {
    protected $test_dir;
    protected $yatt;
    protected $passed = 0;
    protected $failed = 0;
    protected $current_test = '';
    protected $created = array();

    protected function setUp() {
        error_log("Test setUp: Creating test directory");
        $this->test_dir = __DIR__ . '/test_templates';
        if (!file_exists($this->test_dir)) {
            mkdir($this->test_dir, 0777, true);
        }
        error_log("Test setUp: Creating YATT instance with template_path=" . $this->test_dir);
        $this->yatt = new YATT($this->test_dir);
    }

    protected function tearDown() {
        foreach ($this->created as $filename) {
            $path = $this->test_dir . '/' . $filename;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    protected function createTestFile($filename, $content) {
        $this->created[] = $filename;
        file_put_contents($this->test_dir . '/' . $filename, $content);
    }

    protected function assertNotEmpty($value, $message = '') {
        if (empty($value)) {
            $this->fail("Expected non-empty value" . ($message ? ": $message" : ""));
        }
        $this->pass();
    }

    protected function assertNotFalse($value, $message = '') {
        if ($value === false) {
            $this->fail("Expected non-false value" . ($message ? ": $message" : ""));
        }
        $this->pass();
    }

    protected function assertFalse($value, $message = '') {
        if ($value !== false) {
            $this->fail("Expected false value" . ($message ? ": $message" : ""));
        }
        $this->pass();
    }

    protected function assertStringContainsString($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) === false) {
            $this->fail("Expected string to contain '$needle'" . ($message ? ": $message" : ""));
        }
        $this->pass();
    }

    protected function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            $this->fail("Expected '$expected', got '$actual'" . ($message ? ": $message" : ""));
        }
        $this->pass();
    }

    protected function fail($message) {
        $this->failed++;
        echo "FAILED: {$this->current_test}\n";
        echo "  $message\n";
        if (isset($this->yatt->context)) {
            error_log("ERROR: Context stack at failure:");
            error_log(print_r($this->yatt->context->getStack(), true));
        }
        exit(1);
    }

    protected function pass() {
        $this->passed++;
    }

    public function run() {
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                $this->current_test = $method;
                $this->setUp();
                $this->$method();
                $this->tearDown();
            }
        }
        echo "\nTest Summary:\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        if ($this->failed > 0) {
            exit(1);
        }
    }
}

class YATTContextTest extends TestCase {
    public function testBasicErrorReporting() {
        error_log("Test testBasicErrorReporting: Creating test file");
        $this->createTestFile('test1.yatt', "%begin [test]\nLine 1\n%[invalid-var]\nLine 3\n%end [test]");
        error_log("Test testBasicErrorReporting: Loading file");
        $this->yatt->load('test1.yatt');
        error_log("Test testBasicErrorReporting: Parsing template");
        $this->yatt->parse('test');
        error_log("Test testBasicErrorReporting: Getting output");
        $this->yatt->output('test');
        error_log("Test testBasicErrorReporting: Getting errors");
        $errors = $this->yatt->get_errors();
        $this->assertNotFalse($errors, "get_errors() should return array, not FALSE");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('test1.yatt:3', $errors[0]);
    }

    public function testIncludeErrorReporting() {
        $this->createTestFile('main.yatt', "%begin [test]\nLine 1\n%include [sub.yatt]\nLine 3\n%end [test]");
        $this->createTestFile('sub.yatt', "Sub Line 1\n%[invalid-var]\nSub Line 3");

        $this->yatt->load('main.yatt');
        $this->yatt->parse('test');
        $this->yatt->output('test');
        $errors = $this->yatt->get_errors();
        $this->assertNotFalse($errors, "get_errors() should return array, not FALSE");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('main.yatt:3 -> sub.yatt:2', $errors[0]);
    }

    public function testCommentStrippingLineNumbers() {
        $this->createTestFile('comments.yatt',
            "%begin [test]\nLine 1\n%[# This is a comment]\nLine 3\n%[invalid-var]\nLine 5\n%end [test]");

        $this->yatt->load('comments.yatt');
        $this->yatt->parse('test');
        $this->yatt->output('test');
        $errors = $this->yatt->get_errors();
        $this->assertNotFalse($errors, "get_errors() should return array, not FALSE");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('comments.yatt:5', $errors[0]);
    }

    public function testBlockContext() {
        $this->createTestFile('blocks.yatt',
            "%begin [test]\nLine 1\n%begin [inner]\n%[invalid-var]\n%end [inner]\nLine 5\n%end [test]");

        $this->yatt->load('blocks.yatt');
        $this->yatt->parse('test');
        $this->yatt->output('test');
        $errors = $this->yatt->get_errors();
        $this->assertNotFalse($errors, "get_errors() should return array, not FALSE");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('blocks.yatt:4', $errors[0]);
    }

    public function testNestedIncludes() {
        $this->createTestFile('main.yatt', "%begin [test]\nLine 1\n%include [sub1.yatt]\nLine 3\n%end [test]");
        $this->createTestFile('sub1.yatt', "Sub1 Line 1\n%include [sub2.yatt]\nSub1 Line 3");
        $this->createTestFile('sub2.yatt', "Sub2 Line 1\n%[invalid-var]\nSub2 Line 3");

        $this->yatt->load('main.yatt');
        $this->yatt->parse('test');
        $this->yatt->output('test');
        $errors = $this->yatt->get_errors();
        $this->assertNotFalse($errors, "get_errors() should return array, not FALSE");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('main.yatt:3 -> sub1.yatt:2 -> sub2.yatt:2', $errors[0]);
    }

    public function testInvalidBlockName() {
        $this->createTestFile('invalid_block.yatt',
            "%begin [test]\nLine 1\n%begin [invalid@block]\nLine 3\n%end [invalid@block]\nLine 5\n%end [test]");

        $this->yatt->load('invalid_block.yatt');
        $this->yatt->parse('test');
        $this->yatt->output('test');
        $errors = $this->yatt->get_errors();
        $this->assertNotFalse($errors, "get_errors() should return array, not FALSE");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('invalid_block.yatt:3', $errors[0]);
    }

    public function testInvalidVariableName() {
        $this->createTestFile('invalid_var.yatt',
            "%begin [test]\nLine 1\n%[invalid@var]\nLine 3\n%end [test]");

        $this->yatt->load('invalid_var.yatt');
        $this->yatt->parse('test');
        $this->yatt->output('test');
        $errors = $this->yatt->get_errors();
        $this->assertNotFalse($errors, "get_errors() should return array, not FALSE");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('invalid_var.yatt:3', $errors[0]);
    }

    public function testMissingIncludeFile() {
        $this->createTestFile('missing_include.yatt',
            "%begin [test]\nLine 1\n%include [nonexistent.yatt]\nLine 3\n%end [test]");

        $this->yatt->load('missing_include.yatt');
        $this->yatt->parse('test');
        $this->yatt->output('test');
        $errors = $this->yatt->get_errors();
        $this->assertNotFalse($errors, "get_errors() should return array, not FALSE");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('missing_include.yatt:3', $errors[0]);
    }

    public function testNoErrors() {
        $this->createTestFile('valid.yatt', "%begin [test]\nLine 1\n%[valid-var]\nLine 3\n%end [test]");
        $this->yatt->set('valid-var', 'test');
        $this->yatt->load('valid.yatt');
        $this->yatt->parse('test');
        $this->yatt->output('test');
        $errors = $this->yatt->get_errors();
        $this->assertFalse($errors, "get_errors() should return FALSE when no errors");
        $this->assertEquals('', $this->yatt->format_errors(), "format_errors() should return empty string when no errors");
    }
}

// Run the tests
$test = new YATTContextTest();
$test->run();
