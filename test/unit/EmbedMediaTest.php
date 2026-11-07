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

  public function testXStatusUsesTheIdOnly() {
    $out = embed_x_video('https://twitter.com/some_user/status/1234567890?s=20');
    $this->assertStringContainsString('src="https://platform.twitter.com/embed/Tweet.html?id=1234567890"', $out);
    $this->assertStringContainsString('https://x.com/i/status/1234567890', $out);
    $this->assertStringNotContainsString('s=20', $out);
    $this->assertStringNotContainsString('<script', $out);

    $photo = embed_x_video('https://x.com/some_user/status/1234567890/photo/1');
    $this->assertStringContainsString('src="https://platform.twitter.com/embed/Tweet.html?id=1234567890"', $photo);
    $this->assertStringNotContainsString('photo', $photo);

    $mobile = embed_x_video('https://mobile.twitter.com/i/web/status/1234567890');
    $this->assertStringContainsString('src="https://platform.twitter.com/embed/Tweet.html?id=1234567890"', $mobile);
  }

  public function testXRejectsOtherHostsAndBrokenIds() {
    $this->assertNull(embed_x_video('https://twitter.com.evil.com/user/status/1234567890'));
    $this->assertNull(embed_x_video('https://x.com/user/status/abc'));
    $this->assertNull(embed_x_video('https://x.com/user/status/12<script>'));
  }

  public function testThreadsPermalinkAndShortLink() {
    $out = embed_threads_video('https://www.threads.net/@some.user/post/AbC123_xyz/?igsh=tracking');
    $this->assertStringContainsString('src="https://www.threads.com/@some.user/post/AbC123_xyz/embed"', $out);
    $this->assertStringContainsString('https://www.threads.com/@some.user/post/AbC123_xyz', $out);
    $this->assertStringNotContainsString('igsh', $out);
    $this->assertStringNotContainsString('threads.net', $out);
    $this->assertStringNotContainsString('<script', $out);

    $short = embed_threads_video('https://www.threads.com/t/AbC123_xyz/');
    $this->assertStringContainsString('src="https://www.threads.com/t/AbC123_xyz/embed"', $short);
  }

  public function testThreadsRejectsMarkupAndOtherHosts() {
    $this->assertNull(embed_threads_video('https://www.threads.com/@user/post/ab"><script>/'));
    $this->assertNull(embed_threads_video('https://www.threads.net/t/ab"><script>'));
    $this->assertNull(embed_threads_video('https://threads.com.evil.com/@user/post/AbC123'));
  }

  public function testRedditPostIgnoresSlugAndTracking() {
    $out = embed_reddit_video('https://www.reddit.com/r/aww/comments/abc123/cute_cat/?utm_source=share');
    $this->assertStringContainsString(
      'src="https://www.redditmedia.com/r/aww/comments/abc123/?ref_source=embed&ref=share&embed=true"',
      $out
    );
    $this->assertStringContainsString('https://www.reddit.com/r/aww/comments/abc123/', $out);
    $this->assertStringNotContainsString('cute_cat', $out);
    $this->assertStringNotContainsString('utm_source', $out);
    $this->assertStringNotContainsString('<script', $out);

    $old = embed_reddit_video('https://old.reddit.com/r/aww/comments/abc123/title/');
    $this->assertStringContainsString('redditmedia.com/r/aww/comments/abc123/', $old);
  }

  public function testRedditCommentPermalink() {
    $out = embed_reddit_video('https://www.reddit.com/r/news/comments/abc123/some_title/def456/?utm_source=share');
    $this->assertStringContainsString(
      'src="https://www.redditmedia.com/r/news/comments/abc123/_/def456?embed=true&depth=1&showmore=false"',
      $out
    );
    $this->assertStringContainsString('https://www.reddit.com/r/news/comments/abc123/_/def456/', $out);
    $this->assertStringNotContainsString('some_title', $out);
    $this->assertStringNotContainsString('utm_source', $out);
  }

  public function testRedditRejectsBrokenIdsAndOtherHosts() {
    $this->assertNull(embed_reddit_video('https://www.reddit.com/r/aww/comments/ab<script>/'));
    $this->assertNull(embed_reddit_video('https://www.reddit.com.evil.com/r/aww/comments/abc123/'));
    $this->assertNull(embed_reddit_video('https://redd.it/abc123'));
    $this->assertNull(embed_reddit_video('https://www.reddit.com/r/aww/s/AbCdEf'));
    $this->assertNull(embed_reddit_video('https://www.reddit.com/gallery/abc123'));
  }
}
?>
