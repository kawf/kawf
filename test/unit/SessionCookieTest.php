<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
$include_path = "$kawf_base:$kawf_base/include:$kawf_base/lib:$kawf_base/user/account";
$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

require_once($kawf_base . "/user/account/forumuser.inc.php");

use PHPUnit\Framework\TestCase;

class SessionCookieTest extends TestCase {
  public function testNewSessionCookie() {
    $a = new_session_cookie();
    $b = new_session_cookie();

    $this->assertLessThanOrEqual(50, strlen($a));
    $this->assertLessThanOrEqual(50, strlen($b));
    $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $a);
    $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $b);
    $this->assertNotEquals($a, $b);
  }
}
?>
