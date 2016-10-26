#!/usr/bin/php
<?php

$kawf_base = realpath(dirname(__FILE__) . "/..");
require_once($kawf_base . "/include/Skip32.inc.php");
require_once($kawf_base . "/config/config.inc");

print Skip32::decrypt($viewer_aid_key, hexdec($argv[1]))."\n";
?>
