<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
require_once($kawf_base . "/user/embed-media.inc.php");

use PHPUnit\Framework\TestCase;

class EmbedMediaTest extends TestCase {
  public function testInstagramReelUsesTheIdOnly() {
    $out = embed_instagram_video('https://www.instagram.com/reel/AbC123_xyz/?igsh=tracking');
    $this->assertStringContainsString('src="https://www.instagram.com/reel/AbC123_xyz/embed"', $out);
    $this->assertStringNotContainsString('igsh', $out);
    $this->assertStringNotContainsString('<script', $out);
  }

  public function testInstagramDropsMarkupInTheId() {
    $out = embed_instagram_video('https://www.instagram.com/p/ab"><script>/');
    $this->assertStringContainsString('src="https://www.instagram.com/p/ab/embed"', $out);
    $this->assertStringNotContainsString('<script', $out);
  }

  public function testInstagramRejectsOtherHosts() {
    $this->assertNull(embed_instagram_video('https://instagram.com.evil.com/p/AbC123'));
    $this->assertNull(embed_instagram_video('https://evil.com/reel/AbC123'));
  }

  public function testFacebookWatchIgnoresExtraQuery() {
    $out = embed_facebook_video('https://www.facebook.com/watch/?v=1234567890&href=https://evil.com');
    $this->assertStringContainsString(
      'href=' . rawurlencode('https://www.facebook.com/watch/?v=1234567890'),
      $out
    );
    $this->assertStringNotContainsString('evil.com', $out);
  }

  public function testFacebookShortLink() {
    $out = embed_facebook_video('https://fb.watch/AbCd1234/');
    $this->assertStringContainsString(rawurlencode('https://fb.watch/AbCd1234/'), $out);
    $this->assertStringNotContainsString('<script', $out);
  }

  public function testFacebookRejectsABrokenId() {
    $this->assertNull(embed_facebook_video('https://www.facebook.com/watch/?v=12<script>'));
    $this->assertNull(embed_facebook_video('https://www.facebook.com.evil.com/watch/?v=123'));
  }
}
?>
