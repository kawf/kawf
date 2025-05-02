<?php
/**
 * Text wrapping functions for long words and lines
 */

/**
 * Soft break long words at a specified length
 *
 * @param string $text The text to process
 * @param int $length Maximum word length before breaking
 * @return string Text with long words broken
 */
function softbreaklongwords($text, $length = 40) {
    // Split into words
    $words = preg_split('/(\s+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $result = '';

    foreach ($words as $word) {
        // If word is longer than max length, add soft breaks
        if (strlen($word) > $length) {
            $result .= chunk_split($word, $length, '<wbr>');
        } else {
            $result .= $word;
        }
    }

    return $result;
}

/**
 * Wrap text at a specified length
 *
 * @param string $text The text to wrap
 * @param int $length Maximum line length
 * @param string $break Character to use for line breaks
 * @return string Wrapped text
 */
function textwrap($text, $length = 78, $break = "\n") {
    return wordwrap($text, $length, $break, true);
}

/**
 * Process text with both word breaking and line wrapping
 *
 * @param string $text The text to process
 * @param int $word_length Maximum word length before breaking
 * @param int $line_length Maximum line length
 * @return string Processed text
 */
function process_text($text, $word_length = 40, $line_length = 78) {
    $text = softbreaklongwords($text, $word_length);
    return textwrap($text, $line_length);
}
?>
