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

    # INTERNAL: act as a callback for preg_replace_callback below
    function pp_callback($matches) {
        return $this->preprocess($matches[1]);
    }

    # INTERNAL: read file, preprocess for includes, strip out comments
    function preprocess($fname) {
        if (! ($data = file_get_contents($fname, FILE_USE_INCLUDE_PATH))) {
            $this->error('INCLUDE(%s): can not open file!', $fname);
            return '';
        }

        # strip all comments
        $data = preg_replace('/[ \t]*\%\[#\].*$/m', '', $data);

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
        $nchunks = preg_match_all(
                '/(.*?)[ \t]*\%(begin|end)[\t ]+\[([A-Za-z-_]+)\][\t ]*$/ism', 
                $data, $matches, PREG_SET_ORDER);

        # array[1] == text, array[2] == (begin|end), array[3] == NAME
        for ($i = 0; $i < $nchunks; $i++) {
            $text = $matches[$i][1];
            $type = strtolower($matches[$i][2]);
            $name = $matches[$i][3];

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
                    return $this->error('LOAD(%s): Mismatched begin/end: got %s, wanted %s! aborting!',
                                                $fname, $name, $cur[YATT_NAME]);
                }
                if (! ($cur =& my_pop($stack))) {
                    $cur = &$this->obj;
                }
            } else {
                return $this->error('LOAD(%s): unknown tag type %s, aborting!', $fname, $type);
            }
        }
        if (count($stack)) {
            return $this->error('LOAD(%s): mismatched begin/end pairs at EOF!', $fname);
        }
        return count($this->errors);
    }

    # INTERNAL: Find the node that corrosponds to an OID
    function &find_node($path) {
        $oid = explode('.', $path);
        $node = &$this->obj;

        while ($cmp = array_shift($oid)) {
            $old = &$node;
            for ($i = YATT_DSTART; $i < count($node); $i++) {
                if (is_array($node[$i]) && (strcmp($node[$i][YATT_NAME], $cmp) == 0)) {
                        $node = &$node[$i];
                        break;
                }
            }
            if ($old == $node) {
                $this->error('FIND(%s): Could not find node %s', $path, $cmp);
                return FALSE;
            }
        }
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

        for ($i = YATT_DSTART; $i < count($node); $i++) {
            if (is_array($node[$i])) {
                $out .= $this->return_output($node[$i]);
            } else {
                $buf = $node[$i];
                $pass = 0;

                while (preg_match('/\%\[([^][%]+)\]/', $buf)) {
                        if ($pass++ > 10) {
                            # TODO: give the user more info when this happens.
                            $this->error('PARSE(): recursive subst in node %s?', $node[YATT_NAME]);
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
    function YATT() {
        $this->errors = array();
        $this->obj = array('ROOT', '', '');
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
