<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Tests\Exception;

use PHPUnit\Framework\TestCase;
use OwlCorp\CliJsonLint\Exception\RuntimeException;

class RuntimeExceptionTest extends TestCase
{
    public function testIsNativeRuntimeException()
    {
        $this->assertInstanceOf(\RuntimeException::class, new RuntimeException());
    }

    public function testPassesParametersToParent(): void
    {
        $t = $this->createMock('Throwable');
        $e = new RuntimeException('msg', 123, $t);

        $this->assertSame('msg', $e->getMessage());
        $this->assertSame(123, $e->getCode());
        $this->assertSame($t, $e->getPrevious());
    }
}
