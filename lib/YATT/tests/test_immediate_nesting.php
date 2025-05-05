<?php

// Unit Test: Immediate Nesting Behavior

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../YATT.class.php'); // Include YATT class relative to this test file

$test_template_file = __DIR__ . '/test_immediate_nesting.yatt';

$test_results = []; // Store results for summary

function run_test($description, $set_inner_var, $expected_output) {
    global $test_template_file, $test_results;
    // echo "------------------------------------------\n"; // Removed header
    // echo "Test: $description\n"; // Moved to pass/fail message
    // echo "Template: " . basename($test_template_file) . "\n"; // Removed
    // echo "Set INNER_VAR: " . ($set_inner_var ? 'Yes' : 'No') . "\n"; // Removed

    $yatt = new YATT(dirname($test_template_file), basename($test_template_file)); // Load template

    $load_errors = $yatt->get_errors();
    if ($load_errors) {
        echo "!!! FAIL (Load): $description !!!\n";
        echo "Errors during YATT load:\n";
        print_r($load_errors);
        echo "------------------------------------------\n";
        $test_results[$description] = false;
        return false;
    }

    // Conditionally set the inner variable AND parse the child block
    if ($set_inner_var) {
        $yatt->set('INNER_VAR', 'inner_value');
        $yatt->parse('outer_block.inner_block');
        $parse_inner_errors = $yatt->get_errors(); // Check specifically after inner parse
        if ($parse_inner_errors) {
             // Treat parse errors as warnings for now, but could be made failures
             echo "!!! WARN (Parse Inner): $description !!!\n";
             echo "Errors during YATT parse('outer_block.inner_block'):\n";
             print_r($parse_inner_errors);
        }
    }

    // Parse the parent 'outer_block'
    $yatt->parse('outer_block');
    $parse_outer_errors = $yatt->get_errors(); // Check specifically after outer parse
    if ($parse_outer_errors) {
        echo "!!! FAIL (Parse Outer): $description !!!\n";
        echo "Errors during YATT parse('outer_block'):\n";
        print_r($parse_outer_errors);
        echo "------------------------------------------\n";
        $test_results[$description] = false;
        return false;
    }

    // Get the final output (only for the 'outer_block' parsed)
    $actual_output = $yatt->output('outer_block');

    // Compare output
    $actual_norm = str_replace("\r\n", "\n", trim($actual_output));
    $expected_norm = str_replace("\r\n", "\n", trim($expected_output));

    if ($actual_norm === $expected_norm) {
        echo "--- PASS: $description\n"; // Concise pass message
        $test_results[$description] = true;
        return true;
    } else {
        echo "!!! FAIL: $description !!!\n"; // Failure header
        echo "Expected Output:\n<<<EOT\n$expected_output\nEOT\n>>>\n"; // Show details on fail
        echo "Actual Output:\n<<<EOT\n$actual_output\nEOT\n>>>\n";   // Show details on fail
        // Check for any errors collected during the whole process, report only on failure maybe?
        $final_errors = $yatt->get_errors();
        if ($final_errors) {
             echo "WARN: Final errors reported by get_errors():\n";
             print_r($final_errors);
        }
        echo "------------------------------------------\n";
        $test_results[$description] = false;
        return false;
    }
}

// --- Test Execution ---

echo "Starting YATT Immediate Nesting Unit Tests (Generic Names)...\n";

// Test Case 1: INNER_VAR IS set
// Expected: Both links appear, INNER_VAR substituted.
$expected_case_1 = <<<EOT

      <a href="inner:inner_value">Inner Block Link</a> |

<!-- Separator -->
      <a href="#outer">Outer Block Link</a>

EOT;
run_test("Inner variable set", true, $expected_case_1); // Store result internally


// Test Case 2: INNER_VAR IS NOT set
// Expected: Only the Outer link appears. The inner_block is not parsed and should be absent.
$expected_case_2 = <<<EOT
<!-- Separator -->
      <a href="#outer">Outer Block Link</a>
EOT;
run_test("Inner variable NOT set", false, trim($expected_case_2)); // Store result internally


// --- Summary ---
 echo "\nTest Summary:\n";
 $all_passed = true;
 foreach ($test_results as $desc => $passed) {
     echo sprintf("%-30s: %s\n", $desc, ($passed ? "PASS" : "FAIL"));
     if (!$passed) $all_passed = false;
 }

 exit($all_passed ? 0 : 1); // Exit code 0 on success, 1 on failure

 ?>
