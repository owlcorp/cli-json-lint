<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Tests\DTO;

use OwlCorp\CliJsonLint\DTO\LintResult;
use PHPUnit\Framework\TestCase;
use Seld\JsonLint\ParsingException;

final class LintResultTest extends TestCase
{
    public function testAsArrayWithoutErrorExcludesDetailsAndIsValid(): void
    {
        $result = new LintResult(
            sourcePath: '/foo.*',
            filePath: '/foo.json',
            error: null
        );

        $array = $result->asArray(includeDetails: false);

        $this->assertSame(
            ['file' => '/foo.json', 'valid' => true],
            $array
        );

        // Even with details requested, no additional keys when there's no error
        $arrayWithDetails = $result->asArray(includeDetails: true);
        $this->assertArrayNotHasKey('source', $arrayWithDetails);
        $this->assertArrayNotHasKey('error', $arrayWithDetails);
    }

    public function testAsArrayWithErrorExcludesDetailsWhenNotRequested(): void
    {
        $error = $this->createMock(ParsingException::class);
        $result = new LintResult(
            sourcePath: '/foo.*',
            filePath: '/foo.json',
            error: $error
        );

        $array = $result->asArray(includeDetails: false);

        $this->assertSame(
            ['file' => '/foo.json', 'valid' => false],
            $array
        );

        $this->assertArrayNotHasKey('source', $array);
        $this->assertArrayNotHasKey('error', $array);
    }

    public function testAsArrayWithErrorIncludingDetailsAndGlobSource(): void
    {
        $error = $this->createMock(ParsingException::class);
        $error->method('getDetails')->willReturn('pattern error');

        $pattern = '/foo.*';
        $result = new LintResult(
            sourcePath: $pattern,
            filePath: '/foo.json',
            error: $error
        );

        $array = $result->asArray(includeDetails: true);

        $this->assertSame('/foo.json', $array['file']);
        $this->assertFalse($array['valid']);
        $this->assertSame($pattern, $array['source']);
        $this->assertSame('pattern error', $array['error']);
    }
}
