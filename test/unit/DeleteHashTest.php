<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
require_once($kawf_base . "/lib/Upload/Upload.php");

use Kawf\Upload\ImageMetadata;
use Kawf\Upload\Upload;

class DeleteHashProbe extends Upload {
  public function isAvailable(): bool { return true; }
  public function getMaxUploadSize(): int { return 0; }
  public function doUpload(string $filename, string $path, ImageMetadata $metadata): ?string { return null; }
  public function delete(string $path): bool { return false; }
  public function deleteByUrl(string $deleteUrl): bool { return false; }
  public function supports_metadata(): bool { return false; }
  public function save_metadata(string $path, ImageMetadata $metadata): bool { return false; }
  public function load_metadata(string $path): ?ImageMetadata { return null; }
  public function readdir(string $namespace): array { return array(); }

  public function exposeVerify(string $path, string $hash, int $timestamp): bool {
    return $this->verifyDeleteHash($path, $hash, $timestamp);
  }

  public function exposeGenerate(string $path, int $timestamp): string {
    return $this->generateDeleteHash($path, $timestamp);
  }
}

class DeleteHashTest extends PHPUnit_Framework_TestCase {
  public function testWrongHashDoesNotRevealDigest() {
    $uploader = new DeleteHashProbe(array('delete_salt' => 'site-secret'));
    $ok = $uploader->exposeVerify('1/2/a.png', 'wrong', time());

    $this->assertFalse($ok);
    $this->assertEquals(0, preg_match('/[0-9a-f]{64}/', (string)$uploader->getError()));
    $this->assertEquals('Delete link is not valid', $uploader->getError());
  }

  public function testMissingOrPlaceholderSaltFailsClosed() {
    $configs = array(
      array(),
      array('delete_salt' => ''),
      array('delete_salt' => 'default_salt_change_me'),
      array('delete_salt' => 'your_delete_salt'),
    );
    foreach ($configs as $config) {
      $uploader = new DeleteHashProbe($config);
      $this->assertEquals('', $uploader->exposeGenerate('1/2/a.png', time()));
      $this->assertFalse($uploader->exposeVerify('1/2/a.png', '', time()));
    }
  }

  public function testRealSaltVerifiesWithinADay() {
    $uploader = new DeleteHashProbe(array('delete_salt' => 'site-secret'));
    $timestamp = time();
    $hash = $uploader->exposeGenerate('1/2/a.png', $timestamp);

    $this->assertRegExp('/^[0-9a-f]{64}$/', $hash);
    $this->assertTrue($uploader->exposeVerify('1/2/a.png', $hash, $timestamp));
  }
}
?>
