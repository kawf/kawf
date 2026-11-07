<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
require_once($kawf_base . "/include/strip.inc.php");

use PHPUnit\Framework\TestCase;

class StripTest extends TestCase {
  public function testEntityDecode() {
    $this->assertEquals('A test', entity_decode('A test'));
    $this->assertEquals('hello  ? bar', entity_decode('hello &#x20&#x3F bar'));
    $this->assertEquals('hello  ? bar', entity_decode('hello &#x20;&#x3F bar'));
    $this->assertEquals('hello  ? bar', entity_decode('hello &#x20;&#x3F; bar'));
    $this->assertEquals('hello A& bar', entity_decode('hello &#65&#38 bar'));
    $this->assertEquals('hello A/ & bar', entity_decode('hello &#65&#x2f &#38 bar'));
  }

  public function testObjectTagIsEscaped() {
    global $standard_tags;
    $out = striptag('<object data="https://example.com/a.html"></object>', $standard_tags);
    $this->assertStringNotContainsString('<object', $out);
    $this->assertSame(
      '&lt;object data="https://example.com/a.html"&gt;&lt;/object&gt;',
      $out
    );
    $this->assertSame('<b>bold</b>', striptag('<b>bold</b>', $standard_tags));
  }

  public function testAttributeQuoteIsEncoded() {
    global $standard_tags;
    $elements = array(array(
      'attr' => array(0 => 'img', 1 => 'src', 2 => 'alt'),
      'val' => array(1 => 'https://example.com/a.png', 2 => 'say "hi"'),
      'str' => '<img src="https://example.com/a.png" alt="say "hi"">',
    ));
    list($out, $skip) = validate_tag($elements, 0, $standard_tags);
    $this->assertSame(0, $skip);
    $this->assertSame(
      '<img src="https://example.com/a.png" alt="say &quot;hi&quot;">',
      $out
    );
  }
}
?>
