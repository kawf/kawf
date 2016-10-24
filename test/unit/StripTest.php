<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
require_once($kawf_base . "/include/strip.inc");

class StripTest extends PHPUnit_Framework_TestCase {
  public function testEntityDecode() {
    $this->assertEquals('A test', entity_decode('A test'));
    $this->assertEquals('hello  ? bar', entity_decode('hello &#x20&#x3F bar'));
    $this->assertEquals('hello  ? bar', entity_decode('hello &#x20;&#x3F bar'));
    $this->assertEquals('hello  ? bar', entity_decode('hello &#x20;&#x3F; bar'));
    $this->assertEquals('hello A& bar', entity_decode('hello &#65&#38 bar'));
    $this->assertEquals('hello A/ & bar', entity_decode('hello &#65&#x2f &#38 bar'));
  }
}
?>
