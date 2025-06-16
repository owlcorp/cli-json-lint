<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Tests\Command;

use OwlCorp\CliJsonLint\Command\LintJsonCommand;
use OwlCorp\CliJsonLint\Exception\ValueError;
use OwlCorp\CliJsonLint\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;

final class LintJsonCommandTest extends TestCase
{
    private string $tempDir;
    private string $origCwd;

    protected function setUp(): void
    {
        // Create isolated temp directory and switch into it
        $this->tempDir = sys_get_temp_dir() . '/lintjson_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->origCwd = getcwd() ?: '.';
        chdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Restore cwd and remove temp dir
        chdir($this->origCwd);
        $this->removeTempDirectory();
    }

    private function removeTempDirectory(): void
    {
        $dir = $this->tempDir;
        $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
        $todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
            $todo($fileinfo->getRealPath());
        }
        rmdir($dir);
    }

    private function createJsonFile(string $path, string $content): void
    {
        $full = $this->tempDir . '/' . $path;
        if (!is_dir(dirname($full))) {
        mkdir(dirname($full), 0777, true);
        }
        file_put_contents($full, $content);
    }

    public function testInvalidFormatThrowsValueError(): void
    {
        $this->createJsonFile('x.json', '{}');
        $tester = new CommandTester(new LintJsonCommand());
        $this->expectException(ValueError::class);

        $tester->execute([
                              '--format' => 'invalid',
                              'source' => ['x.json'],
                          ], ['catch_exceptions' => false]);
    }

    public function testMixingStdinAndPathsThrowsRuntimeException(): void
    {
        $this->createJsonFile('file.json', '{}');
        $tester = new CommandTester(new LintJsonCommand());
        $this->expectException(RuntimeException::class);

        $tester->execute([
                              'source' => ['-', 'file.json'],
                          ], ['catch_exceptions' => false]);
    }

    public function testDirectoryScanRespectsMaxDepth(): void
    {
        $this->createJsonFile('root/a.json', '{}');
        $this->createJsonFile('root/sub/b.json', '{}');
        $this->createJsonFile('root/sub/deeper/c.json', '{}');

        $tester = new CommandTester(new LintJsonCommand());

        // max-depth = 0: only root/a.json
        $exit0 = $tester->execute([
                                        '--max-depth' => '0',
                                        'source' => ['root'],
                                    ], ['verbosity' => OutputInterface::VERBOSITY_NORMAL]);
        $this->assertSame(0, $exit0);
        $this->assertStringContainsString('All 1 JSON files are valid', $tester->getDisplay());

        // max-depth = 1: root/a.json and root/sub/b.json
        $exit1 = $tester->execute([
                                        '--max-depth' => '1',
                                        'source' => ['root'],
                                    ]);
        $this->assertSame(0, $exit1);
        $this->assertStringContainsString('All 2 JSON files are valid', $tester->getDisplay());
    }

    public function testDefaultGlobPatternProcessesJsonFiles(): void
    {
        $this->createJsonFile('a.json', '{}');
        $this->createJsonFile('b.json', '{}');
        $this->createJsonFile('c.txt', 'ignored');

        $tester = new CommandTester(new LintJsonCommand());
        $exit = $tester->execute([
                                       'source' => ['*.json'],
                                   ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('All 2 JSON files are valid', $tester->getDisplay());
    }

    public function testExcludeOptionFiltersNestedFiles(): void
    {
        $this->createJsonFile('dir/a.json', '{}');
        $this->createJsonFile('dir/sub/b.json', '{}');
        $this->createJsonFile('dir/sub/c.json', '{}');

        $tester = new CommandTester(new LintJsonCommand());

        // Exclude specific file
        $exit1 = $tester->execute([
                                        '--exclude' => ['dir/sub/b.json'],
                                        'source' => ['dir'],
                                    ]);
        $this->assertSame(0, $exit1);
        $this->assertStringContainsString('All 2 JSON files are valid', $tester->getDisplay());
        $this->assertStringNotContainsString('b.json', $tester->getDisplay());

        // Exclude directory path
        $exit2 = $tester->execute([
                                        '--exclude' => ['dir/sub'],
                                        'source' => ['dir'],
                                    ]);
        $this->assertSame(0, $exit2);
        $this->assertStringContainsString('All 1 JSON files are valid', $tester->getDisplay());
        $this->assertStringNotContainsString('b.json', $tester->getDisplay());
        $this->assertStringNotContainsString('c.json', $tester->getDisplay());
    }

    public function testExtensionsOptionFiltersByExtensionInJsonOutput(): void
    {
        $this->createJsonFile('f1.json', '{}');
        $this->createJsonFile('f2.js', '{}');
        $this->createJsonFile('f3.yaml', '{}');

        $tester = new CommandTester(new LintJsonCommand());

        // js only
        $exitJs = $tester->execute([
                                         '--format' => 'json',
                                         '--extensions' => ['js'],
                                         'source' => ['*.{json,js,yaml}'],
                                     ]);
        $this->assertSame(0, $exitJs);
        $jsonJs = json_decode(trim($tester->getDisplay()), true);
        $this->assertCount(1, $jsonJs);
        $this->assertSame('f2.js', $jsonJs[0]['file']);

        // json and yaml
        $exitMulti = $tester->execute([
                                            '--format' => 'json',
                                            '--extensions' => ['json', 'yaml'],
                                            'source' => ['*.{json,js,yaml}'],
                                        ]);
        $this->assertSame(0, $exitMulti);
        $jsonMulti = json_decode(trim($tester->getDisplay()), true);
        $this->assertCount(2, $jsonMulti);
        $files = array_column($jsonMulti, 'file');
        sort($files);
        $this->assertSame(['f1.json', 'f3.yaml'], $files);
    }

    public function testStopOnErrorHaltsProcessing(): void
    {
        $this->createJsonFile('first.json', '{');
        $this->createJsonFile('second.json', '{}');

        $tester = new CommandTester(new LintJsonCommand());
        $exit = $tester->execute([
                                       '--format' => 'jsons',
                                       '--stop-on-error' => true,
                                       'source' => ['*.json'],
                                   ]);

        $this->assertSame(1, $exit);
        $lines = array_filter(preg_split('/\R/', $tester->getDisplay()));
        $this->assertCount(1, $lines);
    }

    public function testRealpathOptionReturnsAbsolutePathsInJsonOutput(): void
    {
        $this->createJsonFile('dir/file.json', '{}');

        $tester = new CommandTester(new LintJsonCommand());
        $exit = $tester->execute([
                                       '--format' => 'json',
                                       '--realpath' => true,
                                       'source' => ['dir'],
                                   ]);

        $this->assertSame(0, $exit);
        $json = json_decode(trim($tester->getDisplay()), true);
        $this->assertStringStartsWith(
        realpath($this->tempDir . '/dir/file.json'),
            $json[0]['file']
        );
    }
}
