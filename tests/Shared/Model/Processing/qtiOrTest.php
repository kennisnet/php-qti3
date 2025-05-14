<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiOr;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class qtiOrTest extends TestCase
{
    private qtiOr $qtiOr;

    protected function setUp(): void
    {
        $this->qtiOr = new qtiOr([]);
    }

    #[Test]
    public function testChildren(): void
    {
        $this->assertEquals([], $this->qtiOr->children());
    }
}
