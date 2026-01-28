<?php

declare(strict_types=1);

namespace Qti3\Tests\Shared\Model\OutcomeProcessing;

use Qti3\Shared\Model\OutcomeProcessing\TestVariables;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TestVariablesTest extends TestCase
{
    private TestVariables $testVariables;

    protected function setUp(): void
    {
        $this->testVariables = new TestVariables('SCORE', 'category');
    }

    #[Test]
    public function testAttributes(): void
    {
        $this->assertEquals(
            ['variable-identifier' => 'SCORE', 'include-category' => 'category'],
            $this->testVariables->attributes(),
        );
    }
}
