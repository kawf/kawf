<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
$include_path = "$kawf_base:$kawf_base/include:$kawf_base/lib";
$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

require_once($kawf_base . "/include/util.inc.php");

use PHPUnit\Framework\TestCase;

class SafeUrlTest extends TestCase {
  public function testSafeLocalUrl() {
    $this->assertEquals('/forum/msgs/1.phtml', safe_local_url('/forum/msgs/1.phtml'));
    $this->assertEquals('msgs/1.phtml', safe_local_url('msgs/1.phtml'));
    $this->assertEquals('example.com/a.png', safe_local_url('example.com/a.png'));

    $this->assertEquals('/', safe_local_url('https://example/'));
    $this->assertEquals('/', safe_local_url('//example'));
    $this->assertEquals('/', safe_local_url('/\\example'));
    $this->assertEquals('/', safe_local_url('/a/../b'));
    $this->assertEquals('/', safe_local_url("/forum/msgs/1.phtml\n"));
  }

  public function testSafeExternalUrl() {
    $this->assertEquals('https://example/a.png', safe_external_url('https://example/a.png'));
    $this->assertEquals('', safe_external_url('javascript:alert(1)'));
    $this->assertEquals('', safe_external_url('data:text/html,hi'));
  }
}
?>
