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

    # Current line number being processed
    var $current_line = 0;

    # Current template filename being processed
    var $current_file = '';

    # INTERNAL: Push an error onto the error stack!
    function error() {
        $args = func_get_args ();
        $format = array_shift($args);
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $bt[0];
        $message = vsprintf($format, $args);
        array_push($this->errors, sprintf("%s:%d: %s", $caller['file'], $caller['line'], $message));
        return count($this->errors);
    }

    # INTERNAL: act as a callback for preg_replace_callback below
    function pp_callback($matches) {
        return $this->preprocess($matches[1]);
    }

    # INTERNAL: read file, preprocess for includes, strip out comments
    function preprocess($fname) {
        $this->current_file = $fname;
        $this->current_line = 0;
        if ($this->template_path)
            $fname = $this->template_path . '/' . $fname;

        if (! ($data = file_get_contents($fname))) {
            $this->error('%s:%d: INCLUDE(%s): can not open file!', $this->current_file, $this->current_line, $fname);
            return '';
        }

        # strip all comments
        $data = preg_replace('/[ \t]*\%\[#\].*$/m', '', $data);

        # Validate block names and variable names
        $lines = explode("\n", $data);
        $seen_vars = array();    # Track which variables we've already reported errors for
        foreach ($lines as $lineno => $line) {
            $this->current_line = $lineno + 1;

            # Check block names
            if (preg_match('/\%(begin|end)[\t ]+\[([^\]]+)\]/', $line, $matches)) {
                $block_name = $matches[2];
                if (!preg_match('/^[A-Za-z0-9-_]+$/', $block_name)) {
                    $this->error('%s:%d: Invalid block name "%s" - only letters, numbers, hyphens and underscores allowed',
                        $this->current_file, $this->current_line, $block_name);
                }
            }

            # Check variable names
            if (preg_match_all('/\%\[([^][%]+)\]/', $line, $matches)) {
                foreach ($matches[1] as $var_name) {
                    if (!preg_match('/^[A-Za-z0-9-_]+$/', $var_name) && !isset($seen_vars[$var_name])) {
                        $this->error('%s:%d: Invalid variable name "%s" - only letters, numbers, hyphens and underscores allowed',
                            $this->current_file, $this->current_line, $var_name);
                        $seen_vars[$var_name] = true;
                    }
                }
            }
        }

        # fetch all includes (recursive!)
        return preg_replace_callback(
                    '/^[ \t]*\%include[ \t]+\[([A-Za-z-_.\/]+)\][ \t]*$/im',
                    array(&$this, 'pp_callback'),
                    $data);
    }

    # load template file on class creation
    # You can load multiple files
    function load($fname) {
        $data = $this->preprocess($fname);
        $stack = array();

        $cur = &$this->obj;
        $pattern = '/(.*?)[ \t]*\%(begin|end)[\t ]+\[([A-Za-z0-9-_]+)\][\t ]*$/ism';
        $nchunks = preg_match_all($pattern, $data, $matches, PREG_SET_ORDER);

        # array[1] == text, array[2] == (begin|end), array[3] == NAME
        for ($i = 0; $i < $nchunks; $i++) {
            $text = $matches[$i][1];
            $type = strtolower($matches[$i][2]);
            $name = $matches[$i][3];

            # Update current_line by counting newlines in the text chunk
            $this->current_line += substr_count($text, "\n");

            if ($text && (strlen($text) > 0)) {
                array_push($cur, $text);
            }

            if (strcasecmp($type, 'begin') == 0) {
                $new = array($name, '', '');
                $cur[] =& $new;
                $stack[] =& $cur;
                $cur = &$new;
                unset($new);
            } else if (strcasecmp($type, 'end') == 0) {
                if (strcmp($cur[YATT_NAME], $name)) {
                    $this->error('%s:%d: Mismatched begin/end: got [%s], wanted [%s]! aborting!',
                        $this->current_file, $this->current_line, $name, $cur[YATT_NAME]);
                    // Reset stack to avoid false EOF errors
                    $stack = array();
                    $cur = &$this->obj;
                } else {
                    if (! ($cur =& my_pop($stack))) {
                        $cur = &$this->obj;
                    }
                }
            } else {
                $this->error('%s:%d: unknown tag type %s, aborting!', $this->current_file, $this->current_line, $type);
                // Reset stack to avoid false EOF errors
                $stack = array();
                $cur = &$this->obj;
            }
        }
        if (count($stack)) {
            $this->error('%s:%d: mismatched begin/end pairs at EOF!', $this->current_file, $this->current_line);
        }
        return count($this->errors);
    }

    # INTERNAL: Find the node that corrosponds to an OID
    function &find_node($path) {
        $oid = $path!=NULL?explode('.', $path):[];
        $node = &$this->obj;
        $false = false;

        while ($cmp = array_shift($oid)) {
            $old = &$node;
            for ($i = YATT_DSTART; $i < count($node); $i++) {
                if (is_array($node[$i]) && (strcmp($node[$i][YATT_NAME], $cmp) == 0)) {
                    $node = &$node[$i];
                    break;
                }
            }
            if ($old == $node) {
                $this->error('%s:%d: FIND(%s): Could not find node %s', $this->current_file, $this->current_line, $path, $cmp);
                return $false;
            }
        }
        return $node;
    }

    # INTERNAL: Substitute some stuff!
    function subst($matches) {
        if (!isset($this->vars[$matches[1]])) {
           $this->error('%s:%d: PARSE(): unbound variable %s', $this->current_file, $this->current_line, $matches[1]);
           return '';
        }
        return $this->vars[$matches[1]];
    }

    # INTERNAL: Build the output for this node
    function build_output(&$root, &$node) {
        $out = '';

        for ($i = YATT_DSTART; $i < count($node); $i++) {
            if (is_array($node[$i])) {
                $out .= $this->return_output($node[$i]);
            } else {
                $buf = $node[$i];
                $pass = 0;

                while (preg_match('/\%\[([^][%]+)\]/', $buf)) {
                    if ($pass++ > 10) {
                        $this->error('%s:%d: PARSE(): recursive subst limit in node %s', $this->current_file, $this->current_line, $node[YATT_NAME]);
                        break;
                    }
                    $buf = preg_replace_callback('/\%\[([^][%]+)\]/', array(&$root, 'subst'), $buf);
                }
                $out .= $buf;
            }
        }
        return $out;
    }

    # INTERNAL: Return all of the generated output
    function return_output(&$node) {
        $out = $node[YATT_OUTPUT];
        $node[YATT_OUTPUT] = '';

        for ($i = YATT_DSTART; $i < count($node); $i++) {
            if (is_array($node[$i])) {
                $out .= $this->return_output($node[$i]);
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
    function parse($path='ROOT') {
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
    function get_errors() {
        return count($this->errors) ? $this->errors : FALSE;
    }

    function format_errors() {
        return implode("\n", $this->errors);
    }
}
// vim: ts=8 sw=4 et:
?>
