<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
require_once($kawf_base . "/user/embed-media.inc.php");

use PHPUnit\Framework\TestCase;

class EmbedMediaTest extends TestCase {
  protected function tearDown(): void {
    unset($GLOBALS['embed_follow_override']);
    unset($GLOBALS['embed_facebook_ratio']);
  }

  protected function follow($url) {
    $GLOBALS['embed_follow_override'] = function () use ($url) {
      return $url;
    };
  }

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

  public function testInstagramProfilePathAndShortHost() {
    $out = embed_instagram_video('https://www.instagram.com/some.user/reel/AbC123_xyz/');
    $this->assertStringContainsString('src="https://www.instagram.com/reel/AbC123_xyz/embed"', $out);

    $short = embed_instagram_video('https://instagr.am/p/AbC123/');
    $this->assertStringContainsString('src="https://www.instagram.com/p/AbC123/embed"', $short);
  }

  public function testInstagramShareLinkUsesTheRedirectTarget() {
    $this->follow('https://www.instagram.com/reel/AbC123_xyz/');
    $out = embed_instagram_video('https://www.instagram.com/share/reel/BAL6eBSNNX?igsh=tracking');
    $this->assertStringContainsString('src="https://www.instagram.com/reel/AbC123_xyz/embed"', $out);
    $this->assertStringNotContainsString('BAL6eBSNNX', $out);
    $this->assertStringNotContainsString('igsh', $out);

    $this->follow(null);
    $this->assertNull(embed_instagram_video('https://www.instagram.com/share/reel/ab"><script>'));
    $this->assertNull(embed_instagram_video('https://www.instagram.com/share/NotAPost'));
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

  public function testFacebookPathAndPostShare() {
    $videos = embed_facebook_video('https://www.facebook.com/some.page/videos/1234567890/');
    $this->assertStringContainsString('plugins/video.php', $videos);
    $this->assertStringContainsString('width="560" height="315"', $videos);
    $this->assertStringContainsString(rawurlencode('https://www.facebook.com/watch/?v=1234567890'), $videos);

    $watch = embed_facebook_video('https://m.facebook.com/watch/1234567890');
    $this->assertStringContainsString('plugins/video.php', $watch);

    $post = embed_facebook_video('https://www.facebook.com/share/p/AbCd1234/?mibextid=tracking');
    $this->assertStringContainsString('plugins/post.php', $post);
    $this->assertStringContainsString(rawurlencode('https://www.facebook.com/share/p/AbCd1234/'), $post);
    $this->assertStringNotContainsString('mibextid', $post);
  }

  public function testFacebookReelKeepsItsAspect() {
    $GLOBALS['embed_facebook_ratio'] = array(1280, 720);
    $out = embed_facebook_video('https://www.facebook.com/reel/3020015265008151/');
    $this->assertStringContainsString('width="560" height="315"', $out);
    $this->assertStringContainsString('&width=560&height=315', $out);
    $this->assertStringContainsString(rawurlencode('https://www.facebook.com/reel/3020015265008151/'), $out);
    $this->assertStringNotContainsString('watch/?v=', $out);
    $this->assertMatchesRegularExpression('/\n     <iframe src=".*\n     <\/iframe>/s', $out);

    $GLOBALS['embed_facebook_ratio'] = array(720, 1280);
    $portrait = embed_facebook_video('https://www.facebook.com/reel/3020015265008151/');
    $this->assertStringContainsString('width="394" height="700"', $portrait);

    $GLOBALS['embed_facebook_ratio'] = null;
    $fallback = embed_facebook_video('https://www.facebook.com/reels/3020015265008151/');
    $this->assertStringContainsString('width="560" height="315"', $fallback);
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

  public function testXLegacyStatusesAndShortLink() {
    $out = embed_x_video('https://twitter.com/some_user/statuses/1234567890');
    $this->assertStringContainsString('src="https://platform.twitter.com/embed/Tweet.html?id=1234567890"', $out);

    $bare = embed_x_video('https://x.com/statuses/1234567890/');
    $this->assertStringContainsString('id=1234567890', $bare);

    $this->follow('https://x.com/i/status/1234567890');
    $short = embed_x_video('https://t.co/AbCd1234?ref=tracking');
    $this->assertStringContainsString('id=1234567890', $short);
    $this->assertStringNotContainsString('AbCd1234', $short);
    $this->assertStringNotContainsString('tracking', $short);
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

    $media = embed_threads_video('https://www.threads.com/@some.user/post/AbC123_xyz/media');
    $this->assertStringContainsString('src="https://www.threads.com/@some.user/post/AbC123_xyz/embed"', $media);
  }

  public function testThreadsShareLinkUsesTheRedirectTarget() {
    $this->follow('https://www.threads.com/@indecentvehicles/post/Dfa8lpFoKFm');
    $out = embed_threads_video('https://www.threads.com/share/BAL6eBSNNX?xmt=tracking');
    $this->assertStringContainsString('src="https://www.threads.com/@indecentvehicles/post/Dfa8lpFoKFm/embed"', $out);
    $this->assertStringNotContainsString('BAL6eBSNNX', $out);
    $this->assertStringNotContainsString('xmt', $out);
    $this->assertStringNotContainsString('<script', $out);

    $this->follow('https://evil.com/@user/post/Dfa8lpFoKFm');
    $this->assertNull(embed_threads_video('https://www.threads.com/share/BAL6eBSNNX'));

    $this->follow(null);
    $this->assertNull(embed_threads_video('https://www.threads.net/share/ab"><script>'));
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

  public function testRedditShortLinks() {
    $short = embed_reddit_video('https://redd.it/abc123');
    $this->assertStringContainsString('src="https://embed.reddit.com/comments/abc123/?ref_source=embed&ref=share&embed=true"', $short);
    $this->assertStringContainsString('https://www.reddit.com/comments/abc123/', $short);

    $gallery = embed_reddit_video('https://www.reddit.com/gallery/abc123/');
    $this->assertStringContainsString('embed.reddit.com/comments/abc123/', $gallery);

    $bare = embed_reddit_video('https://www.reddit.com/comments/abc123');
    $this->assertStringContainsString('embed.reddit.com/comments/abc123/', $bare);

    $this->follow('https://www.reddit.com/r/analog/comments/179exao/photographing_the_eclipse/?share_id=secret&utm_source=share');
    $share = embed_reddit_video('https://www.reddit.com/r/analog/s/hKrTTvFVwZ');
    $this->assertStringContainsString('redditmedia.com/r/analog/comments/179exao/', $share);
    $this->assertStringNotContainsString('hKrTTvFVwZ', $share);
    $this->assertStringNotContainsString('utm_source', $share);
    $this->assertStringNotContainsString('secret', $share);

    $profile = embed_reddit_video('https://www.reddit.com/u/someuser/s/hKrTTvFVwZ');
    $this->assertStringContainsString('comments/179exao/', $profile);
  }

  public function testRedditRejectsBrokenIdsAndOtherHosts() {
    $this->assertNull(embed_reddit_video('https://www.reddit.com/r/aww/comments/ab<script>/'));
    $this->assertNull(embed_reddit_video('https://www.reddit.com.evil.com/r/aww/comments/abc123/'));
    $this->assertNull(embed_reddit_video('https://v.redd.it/abc123'));
    $this->follow(null);
    $this->assertNull(embed_reddit_video('https://www.reddit.com/r/aww/s/ab"><script>'));
  }

  public function testYoutubePathVariants() {
    $live = embed_youtube_video('https://www.youtube.com/live/jfKfPfyJRdk?feature=share');
    $this->assertStringContainsString('src="https://www.youtube.com/embed/jfKfPfyJRdk?rel=0"', $live);
    $this->assertStringNotContainsString('feature', $live);

    $shorts = embed_youtube_video('https://m.youtube.com/shorts/abc12345678/');
    $this->assertStringContainsString('embed/abc12345678?rel=0', $shorts);

    $nocookie = embed_youtube_video('https://www.youtube-nocookie.com/embed/abc12345678');
    $this->assertStringContainsString('embed/abc12345678?rel=0', $nocookie);

    $start = embed_youtube_video('https://youtu.be/abc12345678?t=1m2s');
    $this->assertStringContainsString('embed/abc12345678?rel=0&start=62', $start);

    $this->assertNull(embed_youtube_video('https://youtube.com.evil.com/watch?v=abc12345678'));
    $this->assertNull(embed_youtube_video('https://www.youtube.com/watch?v=ab"><script>'));
  }

  public function testVimeoPathVariants() {
    $hash = embed_vimeo_video('https://vimeo.com/123456789/abcdef?share=copy');
    $this->assertStringContainsString('src="https://player.vimeo.com/video/123456789?h=abcdef"', $hash);
    $this->assertStringNotContainsString('share', $hash);

    $player = embed_vimeo_video('https://player.vimeo.com/video/123456789');
    $this->assertStringContainsString('player.vimeo.com/video/123456789"', $player);

    $channel = embed_vimeo_video('https://vimeo.com/channels/staffpicks/123456789/');
    $this->assertStringContainsString('player.vimeo.com/video/123456789"', $channel);

    $query = embed_vimeo_video('https://vimeo.com/123456789?h=abcdef&fl=1');
    $this->assertStringContainsString('?h=abcdef"', $query);
    $this->assertStringNotContainsString('fl=1', $query);

    $this->assertNull(embed_vimeo_video('https://vimeo.com.evil.com/123456789'));
  }

  public function testRejectedLinkListsPatternsForThatSite() {
    $out = embed_video_rejected('https://www.threads.com/@user"><script>');
    $this->assertStringContainsString('not a supported Threads link', $out);
    $this->assertStringContainsString('https://www.threads.com/@user/post/XXXXXXXXXXX/', $out);
    $this->assertStringContainsString('https://www.threads.com/share/XXXXXXXXXXX', $out);
    $this->assertStringContainsString('href="/tips/#videos"', $out);
    $this->assertStringNotContainsString('youtube.com/watch', $out);
    $this->assertStringNotContainsString('<script', $out);

    $this->assertStringContainsString('not a supported YouTube link', embed_video_rejected('https://m.youtube.com/channel/UC'));
    $this->assertStringContainsString('not a supported X link', embed_video_rejected('https://t.co/not-a-status'));
  }

  public function testRejectedUnknownHostPointsAtTips() {
    $out = embed_video_rejected('https://example.com/watch?v=1');
    $this->assertStringContainsString('href="/tips/#videos"', $out);
    $this->assertStringContainsString('ogg, mp4, or WebM', $out);
    $this->assertStringNotContainsString('<ul>', $out);

    $lookalike = embed_video_rejected('https://threads.com.evil.com/share/AbC');
    $this->assertStringNotContainsString('supported Threads link', $lookalike);
    $this->assertStringContainsString('href="/tips/#videos"', $lookalike);
  }

  public function testHttpsRedirectTargetIsHostLocked() {
    $this->assertSame(
      'https://www.threads.com/@user/post/AbC123',
      embed_https_url('https://www.threads.com/@user/post/AbC123?xmt=secret#frag', 'threads\.(?:com|net)')
    );
    $this->assertNull(embed_https_url('http://www.threads.com/@user/post/AbC123', 'threads\.(?:com|net)'));
    $this->assertNull(embed_https_url('https://user:pass@www.threads.com/@user/post/AbC123', 'threads\.(?:com|net)'));
    $this->assertNull(embed_https_url('https://www.threads.com:8443/@user/post/AbC123', 'threads\.(?:com|net)'));
    $this->assertNull(embed_https_url('https://threads.com.evil.com/@user/post/AbC123', 'threads\.(?:com|net)'));
    $this->assertNull(embed_absolute_https(
      'https://www.threads.com/share/BAL6eBSNNX',
      'https://evil.com/@user/post/AbC123',
      'threads\.(?:com|net)'
    ));
    $this->assertSame(
      'https://www.threads.com/@user/post/AbC123',
      embed_absolute_https('https://www.threads.com/share/BAL6eBSNNX', '/@user/post/AbC123?xmt=1', 'threads\.(?:com|net)')
    );
  }
}
?>
