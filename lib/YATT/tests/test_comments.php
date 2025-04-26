<?php

// Unit Test: Comment Handling

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../YATT.class.php');

$test_template_file = __DIR__ . '/test_comments.yatt';
$test_results = [];

// Basic test runner function (adapted)
function run_test($description, $parse_order, $block_to_output, $expected_output) {
    global $test_template_file, $test_results;

    $yatt = new YATT(dirname($test_template_file), basename($test_template_file));
    $load_errors = $yatt->get_errors();
    if ($load_errors) {
        echo "!!! FAIL (Load): $description !!!\n"; print_r($load_errors); echo "------------------------------------------\n";
        $test_results[$description] = false; return false;
    }

    $parse_errors = [];
    foreach ($parse_order as $block_to_parse) {
        $yatt->parse($block_to_parse);
        $errors = $yatt->get_errors();
        if ($errors) { $parse_errors[$block_to_parse] = $errors; }
    }

    if (!empty($parse_errors)) {
        echo "!!! FAIL (Parse): $description !!!\n"; print_r($parse_errors); echo "------------------------------------------\n";
        $test_results[$description] = false; return false;
    }

    $actual_output = $yatt->output($block_to_output);

    $actual_norm = str_replace("\r\n", "\n", trim($actual_output));
    $expected_norm = str_replace("\r\n", "\n", trim($expected_output));

    if ($actual_norm === $expected_norm) {
        echo "--- PASS: $description\n";
        $test_results[$description] = true; return true;
    } else {
        echo "!!! FAIL: $description !!!\n";
        echo "Expected Output:\n<<<EOT\n$expected_output\nEOT\n>>>\n";
        echo "Actual Output:\n<<<EOT\n$actual_output\nEOT\n>>>\n";
        echo "------------------------------------------\n";
        $test_results[$description] = false; return false;
    }
}

// --- Test Execution ---
echo "Starting YATT Comment Handling Unit Tests...\n";

// Test Case 1: Check if %[#] comments are ignored
$expected_1 = <<<EOT
Visible Before.
Visible After.
EOT;

run_test(
    "Comments are ignored",
    ['comment_test'],
    'comment_test',
    trim($expected_1)
);


// --- Summary ---
 echo "\nTest Summary:\n";
 $all_passed = true;
 foreach ($test_results as $desc => $passed) {
     echo sprintf("%-40s: %s\n", $desc, ($passed ? "PASS" : "FAIL"));
     if (!$passed) $all_passed = false;
 }
 exit($all_passed ? 0 : 1);
?>
