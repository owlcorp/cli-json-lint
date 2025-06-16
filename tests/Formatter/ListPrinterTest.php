<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Tests\Formatter;

use OwlCorp\CliJsonLint\Formatter\ListPrinter;
use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use OwlCorp\CliJsonLint\DTO\LintResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Seld\JsonLint\ParsingException;

final class ListPrinterTest extends TestCase
{
    public function testPrintNoResultsProducesNoOutput(): void
    {
        $collection = new LintResultCollection();
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->never())
           ->method('writeln');

        (new ListPrinter())->printResults($collection, $io);
    }

    public function testPrintNoErrorsProducesNoOutput(): void
    {
        $collection = new LintResultCollection();
        $collection->add(new LintResult('/foo.*', '/ok1.json', null));
        $collection->add(new LintResult('/foo.*', '/ok2.json', null));

        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->never())
           ->method('writeln');

        (new ListPrinter())->printResults($collection, $io);
    }

    public function testPrintSingleErrorPrintsFilePath(): void
    {
        $collection = new LintResultCollection();
        $error = $this->createMock(ParsingException::class);
        $collection->add(new LintResult('/foo.*', '/err.json', $error));

        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())
           ->method('writeln')
           ->with('/err.json');

        (new ListPrinter())->printResults($collection, $io);
    }

    public function testPrintMultipleErrorsPrintsAllFilePathsInOrder(): void
    {
        $collection = new LintResultCollection();
        $err1 = $this->createMock(ParsingException::class);
        $err2 = $this->createMock(ParsingException::class);
        $collection->add(new LintResult('/foo.*', '/err1.json', $err1));
        $collection->add(new LintResult('/foo.*', '/err2.json', $err2));

        $calls = [];
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(2))
           ->method('writeln')
           ->willReturnCallback(function(string $output) use (&$calls) {
               $calls[] = $output;
           });

        (new ListPrinter())->printResults($collection, $io);

        $this->assertSame(['/err1.json', '/err2.json'], $calls);
    }

    public function testPrintMixedResultsPrintsOnlyErrors(): void
    {
        $collection = new LintResultCollection();
        $collection->add(new LintResult('/foo.*', '/ok.json', null));
        $err = $this->createMock(ParsingException::class);
        $collection->add(new LintResult('/foo.*', '/err.json', $err));

        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())
           ->method('writeln')
           ->with('/err.json');

        (new ListPrinter())->printResults($collection, $io);
    }
}
