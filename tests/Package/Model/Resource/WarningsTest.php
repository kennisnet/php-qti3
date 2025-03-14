<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Warnings;
use App\SharedKernel\Domain\StringCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WarningsTest extends TestCase
{
    #[Test]
    public function testWarnings(): void
    {
        $warnings = new Warnings(new StringCollection(['content']));

        $this->assertEquals('warnings.txt', $warnings->href);
        $this->assertFalse($warnings->files->first()->isBinary());
        $this->assertInstanceOf(MemoryFileContent::class, $warnings->files->first()->getContent());
    }
}
