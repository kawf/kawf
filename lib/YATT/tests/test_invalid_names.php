<?php
require_once(__DIR__ . "/../YATT.class.php");

$tpl = new YATT(dirname(__FILE__), "test_invalid_names.yatt");

// Test loading the template
$errors = $tpl->get_errors();
if (!$errors) {
    echo "FAIL: No errors reported for invalid names\n";
    exit(1);
}

// Check that we got the expected number of errors
$expected_errors = 6; // 2 invalid blocks (begin+end) + 2 invalid variables
if (count($errors) != $expected_errors) {
    echo "FAIL: Expected $expected_errors errors, got " . count($errors) . "\n";
    echo "Errors:\n" . implode("\n", $errors) . "\n";
    exit(1);
}

// Check that each error message contains the expected text
$expected_messages = array(
    'Invalid block name "invalid.block"',
    'Invalid block name "block/with/slashes"',
    'Invalid variable name "INVALID@VAR"',
    'Invalid variable name "INVALID VAR"'
);

// Count how many times each expected message appears
$message_counts = array();
foreach ($expected_messages as $msg) {
    $message_counts[$msg] = 0;
    foreach ($errors as $error) {
        if (strpos($error, $msg) !== false) {
            $message_counts[$msg]++;
        }
    }
}

// Verify counts
$expected_counts = array(
    'Invalid block name "invalid.block"' => 2,  // begin + end
    'Invalid block name "block/with/slashes"' => 2,  // begin + end
    'Invalid variable name "INVALID@VAR"' => 1,
    'Invalid variable name "INVALID VAR"' => 1
);

foreach ($expected_counts as $msg => $count) {
    if ($message_counts[$msg] != $count) {
        echo "FAIL: Expected $count occurrences of '$msg', got {$message_counts[$msg]}\n";
        echo "Errors:\n" . implode("\n", $errors) . "\n";
        exit(1);
    }
}

echo "PASS: All invalid name tests passed\n";
exit(0);
?>
