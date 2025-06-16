<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Tests\Exception;

use OwlCorp\CliJsonLint\Exception\ValueError;
use PHPUnit\Framework\TestCase;

class ValueErrorTest extends TestCase
{
    public function testIsNativeValueError()
    {
        $this->assertInstanceOf(\ValueError::class, new ValueError());
    }

    public function testPassesParametersToParent(): void
    {
        $t = $this->createMock('Throwable');
        $e = new ValueError('msg', 123, $t);

        $this->assertSame('msg', $e->getMessage());
        $this->assertSame(123, $e->getCode());
        $this->assertSame($t, $e->getPrevious());
    }
}
