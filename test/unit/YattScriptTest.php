<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class YattScriptTest extends TestCase {
  public static function scripts(): array {
    $dir = dirname(__DIR__, 2) . '/lib/YATT/tests';
    $cases = array();
    foreach (glob($dir . '/test_*.php') as $script) {
      // test_context.php expects source line numbers YATT does not report.
      if (basename($script) === 'test_context.php') {
        continue;
      }
      $cases[basename($script)] = array($script);
    }
    return $cases;
  }

  #[DataProvider('scripts')]
  public function testScript(string $script): void {
    $proc = proc_open(
      array(PHP_BINARY, $script),
      array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
      $pipes,
      dirname($script)
    );
    $this->assertNotFalse($proc);

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($proc);

    $this->assertSame(0, $status, $stdout . $stderr);
  }
}
?>
