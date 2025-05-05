<?php

// Unit Test: Parsing multiple blocks before final output

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../YATT.class.php');

$test_template_file = __DIR__ . '/test_multi_parse.yatt';
$test_results = [];

// Basic test runner function (adapted)
function run_test($description, $parse_order, $vars_to_set, $block_to_output, $expected_output) {
    global $test_template_file, $test_results;

    $yatt = new YATT(dirname($test_template_file), basename($test_template_file));
    $load_errors = $yatt->get_errors();
    if ($load_errors) {
        echo "!!! FAIL (Load): $description !!!\n"; print_r($load_errors); echo "------------------------------------------\n";
        $test_results[$description] = false; return false;
    }

    foreach ($vars_to_set as $key => $value) {
        $yatt->set($key, $value);
    }

    $parse_errors = [];
    foreach ($parse_order as $block_to_parse) {
        $yatt->parse($block_to_parse);
        $errors = $yatt->get_errors();
        if ($errors) { $parse_errors[$block_to_parse] = $errors; }
    }

    if (!empty($parse_errors)) {
        // Treat parse errors as failures
        echo "!!! FAIL (Parse): $description !!!\n";
        echo "Errors during YATT parse():\n"; print_r($parse_errors);
        echo "------------------------------------------\n";
        $test_results[$description] = false; return false;
    }

    $actual_output = $yatt->output($block_to_output);

    $actual_norm = str_replace("\r\n", "\n", $actual_output);
    $expected_norm = str_replace("\r\n", "\n", $expected_output);

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
echo "Starting YATT Multi-Parse Unit Tests...\n";

// Test Case 1: Parse A, then B, output Container
$expected_1 = <<<EOT


    Content Block A (Value A)

  Separator

    Content Block B (Value B)


EOT;
run_test(
    "Parse A, Parse B, Output Container",
    ['container.block_1', 'container.block_2', 'container'],
    ['VAR_A' => 'Value A', 'VAR_B' => 'Value B'],
    'container',
    $expected_1
);

// Test Case 2: Parse only A, output Container (B should be missing)
$expected_2 = <<<EOT


    Content Block A (Value A)

  Separator


EOT;
run_test(
    "Parse A, Output Container",
    ['container.block_1', 'container'], // Only parse A and container
    ['VAR_A' => 'Value A'],
    'container',
    $expected_2
);

// Test Case 3: Parse only B, output Container (A should be missing)
$expected_3 = <<<EOT


  Separator

    Content Block B (Value B)


EOT;
run_test(
    "Parse B, Output Container",
    ['container.block_2', 'container'], // Only parse B and container
    ['VAR_B' => 'Value B'],
    'container',
    $expected_3
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
