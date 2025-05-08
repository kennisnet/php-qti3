<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\MapResponsePoint;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MapResponsePointTest extends TestCase
{
    private MapResponsePoint $mapResponsePoint;

    protected function setUp(): void
    {
        $this->mapResponsePoint = new MapResponsePoint('identifier');
    }

    #[Test]
    public function testMapResponsePoint(): void
    {
        $this->assertEquals(
            [
                'identifier' => 'identifier',
            ],
            $this->mapResponsePoint->attributes()
        );
    }
}
