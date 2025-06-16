<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Tests\DTO;

use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use OwlCorp\CliJsonLint\DTO\LintResult;
use PHPUnit\Framework\TestCase;
use Seld\JsonLint\ParsingException;

final class LintResultCollectionTest extends TestCase
{
    public function testCollectionIsInitiallyEmpty(): void
    {
        $collection = new LintResultCollection();

        $this->assertCount(0, $collection);
        $this->assertSame(0, $collection->countErrors());
    }

    public function testAddResultsIncrementsCount(): void
    {
        $collection = new LintResultCollection();

        $result1 = new LintResult(sourcePath: '/foo.*', filePath: '/foo1.json', error: null);
        $result2 = new LintResult(sourcePath: '/foo.*', filePath: '/foo2.json', error: null);

        $collection->add($result1);
        $collection->add($result2);

        $this->assertCount(2, $collection);
        $this->assertSame(0, $collection->countErrors());
    }

    public function testCountErrorsIncrementsOnlyForErrors(): void
    {
        $collection = new LintResultCollection();

        $okResult = new LintResult(sourcePath: '/foo.*', filePath: '/ok.json', error: null);
        $errorMock = $this->createMock(ParsingException::class);
        $errorResult1 = new LintResult(sourcePath: '/foo.*', filePath: '/err1.json', error: $errorMock);
        $errorResult2 = new LintResult(sourcePath: '/foo.*', filePath: '/err2.json', error: $errorMock);

        $collection->add($okResult);
        $collection->add($errorResult1);
        $collection->add($errorResult2);

        $this->assertCount(3, $collection);
        $this->assertSame(2, $collection->countErrors());
    }

    public function testIteratorYieldsAddedResultsInOrder(): void
    {
        $collection = new LintResultCollection();

        $resultA = new LintResult(sourcePath: '/foo.*', filePath: '/a.json', error: null);
        $resultB = new LintResult(sourcePath: '/foo.*', filePath: '/b.json', error: null);

        $collection->add($resultA);
        $collection->add($resultB);

        $iterated = [];
        foreach ($collection as $item) {
            $iterated[] = $item;
        }

        $this->assertSame([$resultA, $resultB], $iterated);
    }

    public function testCountableAndIteratorAggregateInterfaces(): void
    {
        $collection = new LintResultCollection();

        // Confirm implements Countable
        $this->assertInstanceOf(\Countable::class, $collection);
        // Confirm implements IteratorAggregate and returns Traversable
        $this->assertInstanceOf(\IteratorAggregate::class, $collection);
        $iterator = $collection->getIterator();
        $this->assertInstanceOf(\Traversable::class, $iterator);
    }
}
