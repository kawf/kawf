<?

require_once('YATT.class.php');

$n = new YATT();

$n->load("example.yatt");

# set a variable
$n->set('CRACK', 'eat me');

# or like this
$n->set(array("FOO" => "something else", "BAR" => "barorific!"));

# Variable substitution is recursive, see how this works in the template
$n->set('FNORK', 'baz');
$n->set('CORK_baz', 'shout off and die');

# Parse a single block of text
$n->parse('faz.test');

# Maybe build a table
foreach (array("ONE", "TWO", "THREE") as $value) {
        $n->set('ROW_NAME', $value);
        $n->parse('faz.table.row');
}
$n->parse('faz.table');

# Comment this out and look at the output.
# Notice that everything *but* what is in faz is displayed.
# Only nodes that you parse() actually display things.
$n->parse('faz');

# print the output for everything.
# You could also tell it where to start, like output('faz.table')
# to only print out stuff from table.
print "--------------------------------------------------------------\n";
print "Output of the template:\n";
print "--------------------------------------------------------------\n";
print $n->output();

# If there were any errors, print them out
# You should probably check this *before* you print out the page
# because these errors will likely cause the page not to be
# built correctly. It is done this way so that you only have to
# check once, and so you can do something cool with the errors
# instead of die().
if (($e = $n->get_errors())) {
    print "--------------------------------------------------------------\n";
    print "errors:\n";
    print "--------------------------------------------------------------\n";

    print_r($e);
}

?>
