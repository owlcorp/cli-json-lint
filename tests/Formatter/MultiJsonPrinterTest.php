<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Tests\Formatter;

use OwlCorp\CliJsonLint\Formatter\MultiJsonPrinter;
use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use OwlCorp\CliJsonLint\DTO\LintResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Seld\JsonLint\ParsingException;

final class MultiJsonPrinterTest extends TestCase
{
    public function testPrintNoResultsProducesNoOutput(): void
    {
        $collection = new LintResultCollection();

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(false);
        $io->expects($this->never())
           ->method('writeln');

        (new MultiJsonPrinter())->printResults($collection, $io);
    }

    public function testPrintSingleResultWithoutDetailsWhenNotVerbose(): void
    {
        $collection = new LintResultCollection();
        $collection->add(new LintResult('/foo.*', '/single.json', null));

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(false);
        $io->expects($this->once())
           ->method('writeln')
           ->with($this->equalTo(\json_encode(['file' => '/single.json', 'valid' => true], JSON_THROW_ON_ERROR)));

        (new MultiJsonPrinter())->printResults($collection, $io);
    }

    public function testPrintMultipleResultsWithoutDetailsWhenNotVerbose(): void
    {
        $collection = new LintResultCollection();
        $collection->add(new LintResult('/foo.*', '/foo1.json', null));
        $collection->add(new LintResult('/foo.*', '/foo2.json', null));

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(false);

        $calls = [];
        $io->expects($this->exactly(2))
           ->method('writeln')
           ->willReturnCallback(function(string $output) use (&$calls) {
               $calls[] = $output;
           });

        (new MultiJsonPrinter())->printResults($collection, $io);

        $expected = [
            \json_encode(['file' => '/foo1.json', 'valid' => true], JSON_THROW_ON_ERROR),
            \json_encode(['file' => '/foo2.json', 'valid' => true], JSON_THROW_ON_ERROR),
        ];
        $this->assertSame($expected, $calls);
    }

    public function testPrintSingleResultWithDetailsWhenVerbose(): void
    {
        $collection = new LintResultCollection();
        $error = $this->createMock(ParsingException::class);
        $error->method('getDetails')->willReturn('detail');
        $collection->add(new LintResult('/foo.*', '/err.json', $error));

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(true);
        $io->expects($this->once())
           ->method('writeln')
           ->with($this->equalTo(\json_encode([
                                                  'file' => '/err.json',
                                                  'valid' => false,
                                                  'source' => '/foo.*',
                                                  'error' => 'detail'
                                              ], JSON_THROW_ON_ERROR)));

        (new MultiJsonPrinter())->printResults($collection, $io);
    }

    public function testPrintMultipleResultsWithDetailsWhenVerbose(): void
    {
        $collection = new LintResultCollection();
        $error1 = $this->createMock(ParsingException::class);
        $error1->method('getDetails')->willReturn('first detail');
        $error2 = $this->createMock(ParsingException::class);
        $error2->method('getDetails')->willReturn('second detail');
        $collection->add(new LintResult('/foo.*', '/err1.json', $error1));
        $collection->add(new LintResult('/foo.*', '/err2.json', $error2));

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('isVerbose')->willReturn(true);

        $calls = [];
        $io->expects($this->exactly(2))
           ->method('writeln')
           ->willReturnCallback(function(string $output) use (&$calls) {
               $calls[] = $output;
           });

        (new MultiJsonPrinter())->printResults($collection, $io);

        $expected = [
            \json_encode([
                             'file' => '/err1.json',
                             'valid' => false,
                             'source' => '/foo.*',
                             'error' => 'first detail'
                         ], JSON_THROW_ON_ERROR),
            \json_encode([
                             'file' => '/err2.json',
                             'valid' => false,
                             'source' => '/foo.*',
                             'error' => 'second detail'
                         ], JSON_THROW_ON_ERROR),
        ];
        $this->assertSame($expected, $calls);
    }
}
