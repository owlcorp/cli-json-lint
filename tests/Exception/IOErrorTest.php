<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Tests\Exception;

use OwlCorp\CliJsonLint\Exception\IOError;
use OwlCorp\CliJsonLint\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

class IOErrorTest extends TestCase
{
    public function testIsAppRuntimeException(): void
    {
        $this->assertInstanceOf(RuntimeException::class, new IOError());
    }

    public function testPassesParametersToParent(): void
    {
        $t = $this->createMock('Throwable');
        $e = new IOError('msg', 123, $t);

        $this->assertSame('msg', $e->getMessage());
        $this->assertSame(123, $e->getCode());
        $this->assertSame($t, $e->getPrevious());
    }
}
