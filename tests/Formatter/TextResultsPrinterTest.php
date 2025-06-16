<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Tests\Formatter;

use OwlCorp\CliJsonLint\DTO\LintResult;
use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use OwlCorp\CliJsonLint\Formatter\TextResultsPrinter;
use PHPUnit\Framework\TestCase;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TextResultsPrinterTest extends TestCase
{
    public function testWarningWhenNoFiles(): void
    {
        $collection = new LintResultCollection();
        $warnings = [];

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(false);
        $io->method('isVeryVerbose')->willReturn(false);
        $io->method('warning')->willReturnCallback(static function (string $message) use (&$warnings): void {
            $warnings[] = $message;
        });

        (new TextResultsPrinter())->printResults($collection, $io);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('No files were parsed', $warnings[0]);
    }

    public function testSuccessWhenAllValidNonVerbose(): void
    {
        $collection = new LintResultCollection();
        $collection->add(new LintResult('/glob/*', '/a.json', null));
        $collection->add(new LintResult('/glob/*', '/b.json', null));

        $successes = [];
        $comments = [];
        $blocks = [];
        $errors = [];
        $warnings = [];

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(false);
        $io->method('isVeryVerbose')->willReturn(false);
        $io->method('success')->willReturnCallback(static function (string $message) use (&$successes): void {
            $successes[] = $message;
        });
        $io->method('comment')->willReturnCallback(static function (string $message) use (&$comments): void {
            $comments[] = $message;
        });
        $io->method('block')->willReturnCallback(static function (string $message) use (&$blocks): void {
            $blocks[] = $message;
        });
        $io->method('error')->willReturnCallback(static function (string $message) use (&$errors): void {
            $errors[] = $message;
        });
        $io->method('warning')->willReturnCallback(static function (string $message) use (&$warnings): void {
            $warnings[] = $message;
        });

        (new TextResultsPrinter())->printResults($collection, $io);

        $this->assertCount(1, $successes);
        $this->assertStringContainsString('All 2 JSON files', $successes[0]);
        $this->assertStringContainsString('are valid', $successes[0]);

        $this->assertEmpty($comments);
        $this->assertEmpty($blocks);
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);
    }

    public function testCommentWhenAllValidVeryVerbose(): void
    {
        $collection = new LintResultCollection();
        $collection->add(new LintResult('/glob/*', '/a.json', null));
        $collection->add(new LintResult('/glob/*', '/b.json', null));

        $comments = [];
        $successes = [];

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(false);
        $io->method('isVeryVerbose')->willReturn(true);
        $io->method('comment')->willReturnCallback(static function (string $message) use (&$comments): void {
            $comments[] = $message;
        });
        $io->method('success')->willReturnCallback(static function (string $message) use (&$successes): void {
            $successes[] = $message;
        });

        (new TextResultsPrinter())->printResults($collection, $io);

        $this->assertCount(2, $comments);
        foreach ($comments as $comment) {
            $this->assertStringContainsString('is valid', $comment);
            $this->assertStringContainsString('JSON', $comment);
        }

        $this->assertCount(1, $successes);
        $this->assertStringContainsString('All 2 JSON files', $successes[0]);
    }

    public function testErrorBlockAndPartialErrorSummaryNonVerbose(): void
    {
        $collection = new LintResultCollection();
        $exception = $this->createMock(ParsingException::class);
        $collection->add(new LintResult('/glob/*', '/err.json', $exception));
        $collection->add(new LintResult('/glob/*', '/ok.json', null));

        $blocks = [];
        $errors = [];
        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(false);
        $io->method('isVeryVerbose')->willReturn(false);
        $io->method('block')->willReturnCallback(static function (string $message) use (&$blocks): void {
            $blocks[] = $message;
        });
        $io->method('error')->willReturnCallback(static function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        (new TextResultsPrinter())->printResults($collection, $io);

        $this->assertCount(1, $blocks);
        $this->assertStringContainsString('Error 1 of 1', $blocks[0]);
        $this->assertStringContainsString('is invalid', $blocks[0]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('1 of 2 JSON files', $errors[0]);
        $this->assertStringContainsString('syntax errors', $errors[0]);
    }

    public function testErrorBlockDetailsAndSummaryVerbose(): void
    {
        $collection = new LintResultCollection();
        $vendorEx = new ParsingException('Detailed syntax error');
        $collection->add(new LintResult('/glob/*', '/err.json', $vendorEx));

        $blocks = [];
        $errors = [];
        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(true);
        $io->method('isVeryVerbose')->willReturn(false);
        $io->method('block')->willReturnCallback(static function (string $message) use (&$blocks): void {
            $blocks[] = $message;
        });
        $io->method('error')->willReturnCallback(static function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        (new TextResultsPrinter())->printResults($collection, $io);

        $this->assertCount(1, $blocks);
        $this->assertStringContainsString('Error 1 of 1', $blocks[0]);
        $this->assertStringContainsString('is invalid', $blocks[0]);
        $this->assertStringContainsString('Detailed syntax error', $blocks[0]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('All 1 JSON files contained syntax errors', $errors[0]);
    }
}
