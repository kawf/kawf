<?php
/*
 * Simple text template class. Does not handle caching.
 *
 * Version 1.1
 *
 */

# here is whats in each array node
define('YATT_NAME',     0);  # node name
define('YATT_OUTPUT',   1);  # output buffer for this node
define('YATT_CONTEXTS', 2);  # not impl. in php: node context
define('YATT_DSTART',   3);  # start of data/chilren nodes

# This sucks, but array_pop doesn't deal well with references
function &my_pop(&$array) {
    if (!count($array)) return null;
    end($array);
    $var =& $array[key($array)];
    array_pop($array);
    return $var;
}

class YATT {
    # Where to pull the templates from
    var $template_path;

    # Holds the template tree
    var $obj;

    # Holds variables
    var $vars = array();

    # Holds information on errors
    var $errors;

    # INTERNAL: Push an error onto the error stack!
    function error() {
        $args = func_get_args ();
        $format = array_shift($args);
        array_push($this->errors, vsprintf($format, $args));
        return count($this->errors);
    }

    # INTERNAL: read file, preprocess for includes, strip out comments
    function preprocess($fname) {
        // Original path logic
        if ($this->template_path)
            $fname = $this->template_path . '/' . $fname;

        if (! ($data = @file_get_contents($fname))) { // Suppress directory warning, check return
            $this->error('INCLUDE(%s): can not open file!', $fname);
            return false; // Return false on failure, consistent
        }

        // strip all comments
        $data = preg_replace('/[ \t]*\%\[#\].*$/m', '', $data);

        // fetch all includes (recursive!)
        return preg_replace_callback(
                    '/^[ \t]*\%include[ \t]+\[([A-Za-z-_.\/]+)\][ \t]*$/im',
                    array(&$this, 'pp_callback'),
                    $data);
    }

    # INTERNAL: act as a callback for preg_replace_callback above
    function pp_callback($matches) {
        // Original simple recursive call
        return $this->preprocess($matches[1]);
    }

    # load template file. more then one can be loaded into a template object.
    function load($fname) {
        // --- Start: Integrated Line-by-Line Parser ---
        $data = $this->preprocess($fname);
        if ($data === false || $data === null) {
            return count($this->errors);
        }
        $lines = preg_split('/\r?\n/', $data);
        $stack = array();
        $cur = &$this->obj;
        $line_num = 0;
        if (!defined('YATT_DSTART')) define('YATT_DSTART', 3);
        // error_log("--- Starting Load (Integrated): $fname ---"); // DEBUG REMOVED

        $current_text = ''; // Initialize text accumulator outside the loop

        foreach ($lines as $line_num => $line) {
            $line_num++; // Adjust to 1-based indexing for errors

            $type = null;
            $name = null;
            // Final Regexes (No space after %, allow leading/trailing ws on line)
            if (preg_match('/^\s*%begin\s+\[([A-Za-z0-9_-]+)\]\s*$/', $line, $matches)) {
                $type = 'begin';
                $name = $matches[1];
            } elseif (preg_match('/^\s*%end\s+\[([A-Za-z0-9_-]+)\]\s*$/', $line, $matches)) {
                $type = 'end';
                $name = $matches[1];
            }

            if ($type !== null) {
                // -- Tag Line --
                // Push any accumulated text before handling the tag
                if ($current_text != '') {
                    if (is_array($cur)) {
                         array_push($cur, $current_text);
                    } else {
                         $error_msg = sprintf('LOAD(%s:%d): Internal parser error, current node is not an array when adding accumulated text.', $fname, $line_num);
                         return $this->error($error_msg);
                    }
                    $current_text = ''; // Reset accumulator
                }

                if ($type == 'begin') {
                    $new = array($name, '', '');
                    $cur[] =& $new;
                    $stack[] =& $cur;
                    $cur = &$new;
                    unset($new);
                } else { // type == 'end'
                    if (!isset($cur[YATT_NAME]) || strcmp($cur[YATT_NAME], $name)) {
                         $error_msg = sprintf('LOAD(%s:%d): Mismatched begin/end: got end %s, expected end %s!',
                                             $fname, $line_num, $name, isset($cur[YATT_NAME]) ? $cur[YATT_NAME] : '(None, at ROOT?)');
                         return $this->error($error_msg);
                    }
                    $parent = &my_pop($stack);
                    if ($parent === null) {
                        $error_msg = sprintf('LOAD(%s:%d): Extra end tag for %s detected.', $fname, $line_num, $name);
                        return $this->error($error_msg);
                    }
                    $cur = &$parent;
                }
            } else {
                // -- Text Line --
                // Only add non-empty lines to accumulator
                // Note: preg_split with PREG_SPLIT_NO_EMPTY might make this check redundant,
                // but let's keep it for safety/clarity. We also ignore empty lines anyway.
                if (strlen(trim($line)) > 0) {
                     // Add the original line content plus a newline to the accumulator
                     $current_text .= $line . "\n";
                }
                // We don't push here; we wait until the next tag or end of loop
            }
        } // end foreach line

        // Push any remaining accumulated text after the loop finishes
        if ($current_text != '') {
            if (is_array($cur)) {
                array_push($cur, $current_text);
            } else {
                // This case should theoretically not happen if parsing ends correctly at root,
                // but added for robustness.
                $error_msg = sprintf('LOAD(%s:EOF): Internal parser error, current node is not an array when adding final text.', $fname);
                return $this->error($error_msg);
            }
        }

        // Final check: Ensure stack is empty
        if (count($stack)) {
             $unclosed_tags = array();
             foreach ($stack as &$nodeRef) {
                 if (isset($nodeRef[YATT_NAME])) {
                     $last_child_idx = count($nodeRef) - 1;
                     if ($last_child_idx >= YATT_DSTART && is_array($nodeRef[$last_child_idx])) {
                         $unclosed_tags[] = $nodeRef[$last_child_idx][YATT_NAME];
                     } else {
                         $unclosed_tags[] = '?UnknownChildOf('.$nodeRef[YATT_NAME].')?';
                     }
                 } else {
                     $unclosed_tags[] = '?UnknownNode?';
                 }
             }
             array_unshift($unclosed_tags, 'ROOT');
             $error_msg = sprintf('LOAD(%s): mismatched begin/end pairs at EOF! Unclosed structure: %s', $fname, implode(' -> ', $unclosed_tags));
             // error_log("--- Load ERROR: $error_msg ---"); // DEBUG REMOVED
             return $this->error($error_msg);
        }

        // error_log("--- Load Complete (Integrated): $fname. Final Structure: ---"); // DEBUG REMOVED
        // error_log(print_r($this->obj, true)); // DEBUG REMOVED

        return count($this->errors);
        // --- End: Integrated Line-by-Line Parser ---
    }

    # INTERNAL: Find the node that corresponds to an OID
    function &find_node($path) {
        $oid = $path!=NULL?explode('.', $path):[];
        $node = &$this->obj; // Start search from root
        $current_path_segment = 'ROOT';
        // error_log("FIND_NODE: Searching for path '$path'");
        $found = false; // Initialize found flag

        while ($cmp = array_shift($oid)) {
            // error_log("FIND_NODE:  -> Looking for component '$cmp' within node '{$node[YATT_NAME]}'");
            $found = false; // Reset for each path component
            $current_path_segment .= '.' . $cmp;

            if (!is_array($node) || count($node) <= YATT_DSTART) {
                 // error_log("FIND_NODE:  -> ERROR: Current node '{$node[YATT_NAME]}' has no children to search.");
                 $result = false; return $result;
            }

            for ($i = YATT_DSTART; $i < count($node); $i++) {
                 // error_log("FIND_NODE:  -> Checking child index $i...");
                 if (!isset($node[$i])) { /* error_log("FIND_NODE:  ->   Index $i not set."); */ continue; }

                 if (is_array($node[$i])) {
                     if (isset($node[$i][YATT_NAME])) {
                         // error_log("FIND_NODE:  ->   Child $i is node '{$node[$i][YATT_NAME]}'");
                         if (strcmp($node[$i][YATT_NAME], $cmp) == 0) {
                             $node = &$node[$i];
                             $found = true;
                             // error_log("FIND_NODE:  ->   FOUND! Moving to node '{$node[YATT_NAME]}'");
                             break;
                         }
                     } else {
                         // error_log("FIND_NODE:  ->   Child $i is array but has no NAME.");
                     }
                 } else {
                     // error_log("FIND_NODE:  ->   Child $i is text, skipping.");
                 }
            }
            if (!$found) {
                $error_msg = sprintf('FIND(%s): Could not find node component \'%s\' within path %s', $path, $cmp, $current_path_segment);
                // error_log("FIND_NODE:  -> ERROR: $error_msg");
                $this->error($error_msg);
                $result = false;
                return $result;
            }
        }
        // error_log("FIND_NODE: Success! Returning node '{$node[YATT_NAME]}'");
        return $node;
    }

    # INTERNAL: Substitute some stuff!
    function subst($matches) {
        if (!isset($this->vars[$matches[1]])) {
           $this->error('PARSE(): unbound variable %s', $matches[1]);
           return '';
        }
        return $this->vars[$matches[1]];
    }

    # INTERNAL: Build the output for this node
    function build_output(&$root, &$node) {
        $out = '';

        // Ensure node is actually an array before proceeding
        if (!is_array($node)) {
            $this->error('BUILD_OUTPUT(): Node provided is not an array!');
            return ''; // Return empty string on error
        }

        for ($i = YATT_DSTART; $i < count($node); $i++) {
            // Check if index exists before accessing
            if (!isset($node[$i])) continue;

            // If the element is an array, it represents a nested block.
            // Append its already parsed content (if any) via return_output.
            if (is_array($node[$i])) {
                $out .= $this->return_output($node[$i]);
            }
            // Otherwise, it's a plain text chunk, process it for variable substitution.
            else {
                $buf = $node[$i];
                // Ensure $buf is a string before regex processing
                if (!is_string($buf)) {
                    $this->error('BUILD_OUTPUT(): Unexpected non-string, non-array node child found in node %s at index %d.', isset($node[YATT_NAME]) ? $node[YATT_NAME] : '?', $i);
                    continue; // Skip this invalid child
                }
                $pass = 0;

                // Perform variable substitution (existing logic)
                while (preg_match('/\%\[([^][%]+)\]/', $buf)) {
                        if ($pass++ > 10) {
                            $this->error('PARSE(): recursive subst limit in node %s?', isset($node[YATT_NAME]) ? $node[YATT_NAME] : '?');
                            break;
                        }
                        $buf = preg_replace_callback('/\%\[([^][%]+)\]/', array(&$root, 'subst'), $buf);
                }
                $out .= $buf;
            }
        }
        return $out;
    }

    # INTERNAL: Return all of the generated output (Recursive, matches Perl)
    function return_output(&$node) {
        $out = isset($node[YATT_OUTPUT]) ? $node[YATT_OUTPUT] : ''; // Handle potentially unset buffer
        if (isset($node[YATT_OUTPUT])) {
            $node[YATT_OUTPUT] = ''; // Clear buffer for this node
        }

        // Recursive loop matches Perl's logic
        for ($i = YATT_DSTART; $i < count($node); $i++) {
            if (is_array($node[$i])) {
                $out .= $this->return_output($node[$i]); // Recursively get output from children
            }
        }
        return $out;
    }

    # Create a new YATT instance.
    function __construct($template_path = null, $filename = null) {
        $this->errors = array();
        $this->obj = array('ROOT', '', '');
	$this->template_path = $template_path;
	if ($filename != null) $this->load($filename);
    }

    # Return output, starting at a given node
    function output($path=NULL) {
        $obj =& $this->find_node($path);
        return $obj ? $this->return_output($obj) : FALSE;
    }

    # Generate text from an object tree
    function parse($path) {
        if ($obj =& $this->find_node($path)) {
            $obj[YATT_OUTPUT] .= $this->build_output($this, $obj);
        }
    }

    # Set a variable to some value
    function set($var, $value=NULL) {
        if (is_array($var)) {
            $this->vars = array_merge($this->vars, $var);
        } else if (is_array($value)) {
	    foreach ($value as $k=>$v) $hash[$var."($k)"]=$v;
	    $this->vars = array_merge($this->vars, $hash);
        } else {
            $this->vars[$var] = $value;
        }
    }

    function reset() {
	$this->vars = array();
    }

    # Get errors, or return FALSE if there are none.
    # Resets error list to zero!
    function get_errors() {
        $err = $this->errors;
        $this->errors = array();
        return count($err) ? $err : FALSE;
    }
}

?>
