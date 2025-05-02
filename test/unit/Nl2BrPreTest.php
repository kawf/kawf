<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
require_once($kawf_base . "/include/nl2brPre.inc.php");

class Nl2BrPreTest extends PHPUnit_Framework_TestCase {
  public function testNl2BrPre() {
    $this->assertEquals("a", nl2brPre::out("a", true));
    $this->assertEquals("a", nl2brPre::out("a", false));

    $this->assertEquals("a<br />\nb", nl2brPre::out("a\nb", true));
    $this->assertEquals("a<br>\nb", nl2brPre::out("a\nb", false));

    $this->assertEquals("a<br />\nb<br />\n", nl2brPre::out("a\nb\n", true));
    $this->assertEquals("a<br>\nb<br>\n", nl2brPre::out("a\nb\n", false));

    $this->assertEquals("<br />\na<br />\nb", nl2brPre::out("\na\nb", true));
    $this->assertEquals("<br>\na<br>\nb", nl2brPre::out("\na\nb", false));

    $this->assertEquals("<pre></pre>", nl2brPre::out("<pre></pre>", true));
    $this->assertEquals("<pre></pre>", nl2brPre::out("<pre></pre>", false));

    $this->assertEquals("<pre>\n</pre>", nl2brPre::out("<pre>\n</pre>", true));
    $this->assertEquals("<pre>\n</pre>", nl2brPre::out("<pre>\n</pre>", false));

    $this->assertEquals("<pre>a</pre>", nl2brPre::out("<pre>a</pre>", true));
    $this->assertEquals("<pre>a</pre>", nl2brPre::out("<pre>a</pre>", false));

    $this->assertEquals("<pre>a</pre><br />\n", nl2brPre::out("<pre>a</pre>\n", true));
    $this->assertEquals("<pre>a</pre><br>\n", nl2brPre::out("<pre>a</pre>\n", false));

    $this->assertEquals("<br />\n<pre>a</pre><br />\n", nl2brPre::out("\n<pre>a</pre>\n", true));
    $this->assertEquals("<br>\n<pre>a</pre><br>\n", nl2brPre::out("\n<pre>a</pre>\n", false));

    $this->assertEquals("<pre>a\n</pre>", nl2brPre::out("<pre>a\n</pre>", true));
    $this->assertEquals("<pre>a\n</pre>", nl2brPre::out("<pre>a\n</pre>", false));

    $this->assertEquals("<pre>\na</pre>", nl2brPre::out("<pre>\na</pre>", true));
    $this->assertEquals("<pre>\na</pre>", nl2brPre::out("<pre>\na</pre>", false));

    $this->assertEquals("<pre>b\n</pre>c<br />\n", nl2brPre::out("<pre>b\n</pre>c\n", true));
    $this->assertEquals("<pre>b\n</pre>c<br>\n", nl2brPre::out("<pre>b\n</pre>c\n", false));

    $this->assertEquals("a<br />\n<pre>b\n</pre>", nl2brPre::out("a\n<pre>b\n</pre>", true));
    $this->assertEquals("a<br>\n<pre>b\n</pre>", nl2brPre::out("a\n<pre>b\n</pre>", false));

    $this->assertEquals("a<br />\n<pre>b\n</pre>c<br />\n", nl2brPre::out("a\n<pre>b\n</pre>c\n", true));
    $this->assertEquals("a<br>\n<pre>b\n</pre>c<br>\n", nl2brPre::out("a\n<pre>b\n</pre>c\n", false));

    $this->assertEquals("a<pre>&lt;pre&gt;b&lt;/pre&gt;c</pre>d", nl2brPre::out("a<pre><pre>b</pre>c</pre>d", true));
    $this->assertEquals("a<pre>&lt;pre&gt;b&lt;/pre&gt;c</pre>d", nl2brPre::out("a<pre><pre>b</pre>c</pre>d", false));

    $this->assertEquals("<pre>a</pre>", nl2brPre::out("<pre>a", true));
    $this->assertEquals("<pre>a</pre>", nl2brPre::out("<pre>a", false));

    $this->assertEquals("<pre>&lt;pre&gt;a</pre>", nl2brPre::out("<pre><pre>a", true));
    $this->assertEquals("<pre>&lt;pre&gt;a</pre>", nl2brPre::out("<pre><pre>a", false));

    $this->assertEquals("<pre>&lt;pre&gt;a&lt;/pre&gt;</pre>", nl2brPre::out("<pre><pre>a</pre>", true));
    $this->assertEquals("<pre>&lt;pre&gt;a&lt;/pre&gt;</pre>", nl2brPre::out("<pre><pre>a</pre>", false));

    $this->assertEquals("<pre>&lt;pre&gt;a&lt;/pre&gt;</pre>", nl2brPre::out("<pre><pre>a</pre></pre>", true));
    $this->assertEquals("<pre>&lt;pre&gt;a&lt;/pre&gt;</pre>", nl2brPre::out("<pre><pre>a</pre></pre>", false));

    $this->assertEquals("<pre></pre>a</pre>", nl2brPre::out("<pre></pre>a</pre>", true));
    $this->assertEquals("<pre></pre>a</pre>", nl2brPre::out("<pre></pre>a</pre>", false));
  }
}
?>
