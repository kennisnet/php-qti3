<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Correct;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CorrectTest extends TestCase
{
    private Correct $correct;

    protected function setUp(): void
    {
        $this->correct = new Correct('correct');
    }

    #[Test]
    public function testCorrect(): void
    {
        $this->assertEquals(
            [
                'identifier' => 'correct',
            ],
            $this->correct->attributes()
        );
    }
}
