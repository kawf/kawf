<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
$include_path = "$kawf_base:$kawf_base/include:$kawf_base/lib:$kawf_base/user/account";
$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

require_once($kawf_base . "/user/account/forumuser.inc.php");

class PendingCookieTest extends PHPUnit_Framework_TestCase {
  public function testNewPendingCookie() {
    $a = new_pending_cookie();
    $b = new_pending_cookie();

    $this->assertEquals(15, strlen($a));
    $this->assertEquals(15, strlen($b));
    $this->assertRegExp('/^[0-9a-f]{15}$/', $a);
    $this->assertRegExp('/^[0-9a-f]{15}$/', $b);
    $this->assertNotEquals($a, $b);
  }
}
?>
