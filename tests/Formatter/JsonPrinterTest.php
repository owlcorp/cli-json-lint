<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Tests\Formatter;

use OwlCorp\CliJsonLint\DTO\LintResult;
use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use OwlCorp\CliJsonLint\Formatter\JsonPrinter;
use PHPUnit\Framework\TestCase;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Style\SymfonyStyle;

final class JsonPrinterTest extends TestCase
{
    public function testPrintResultsWithoutDetailsWhenNotVerbose(): void
    {
        $collection = new LintResultCollection();
        $collection->add(new LintResult('/foo.*', '/foo1.json', null));
        $collection->add(new LintResult('/foo.*', '/foo2.json', null));

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(false);
        $io->expects($this->once())
           ->method('writeln')
           ->with($this->callback(static function (string $output) use ($collection): bool {
               $expected = \array_map(static fn (LintResult $r) => $r->asArray(false), \iterator_to_array($collection));
               return $output === \json_encode($expected, \JSON_PRETTY_PRINT);
           }));

        $printer = new JsonPrinter();
        $printer->printResults($collection, $io);
    }

    public function testPrintResultsWithDetailsWhenVerbose(): void
    {
        $collection = new LintResultCollection();
        $error = $this->createMock(ParsingException::class);
        $error->method('getDetails')->willReturn('error details');
        $collection->add(new LintResult('/foo.*', '/foo.json', $error));

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(true);
        $io->expects($this->once())
           ->method('writeln')
           ->with($this->callback(static function (string $output) use ($collection): bool {
               $expected = \array_map(static fn (LintResult $r) => $r->asArray(true), \iterator_to_array($collection));
               return $output === \json_encode($expected, \JSON_PRETTY_PRINT);
           }));

        $printer = new JsonPrinter();
        $printer->printResults($collection, $io);
    }
}
