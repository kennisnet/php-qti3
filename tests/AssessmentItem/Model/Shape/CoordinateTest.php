<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\Coordinate;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CoordinateTest extends TestCase
{
    private Coordinate $coordinate;

    protected function setUp(): void
    {
        // Initialisatie van een standaard Coordinate object voor tests
        $this->coordinate = new Coordinate('418');
    }

    #[Test]
    public function testToString(): void
    {
        $this->assertSame('418', (string) $this->coordinate);
    }

    #[Test]
    public function testToStringWithPercentage(): void
    {
        $coordinate = new Coordinate('50%');

        $this->assertSame('50%', (string) $coordinate);
    }

    #[Test]
    public function testInvalidCoordinate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid coordinate value: 418px');

        new Coordinate('418px');
    }
}
