<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Model\OutcomeProcessing;

use Qti3\AssessmentTest\Model\OutcomeProcessing\TestVariables;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TestVariablesTest extends TestCase
{
    #[Test]
    public function testAttributes(): void
    {
        $testVariables = new TestVariables('SCORE', 'category');

        $this->assertSame(
            [
                'variable-identifier' => 'SCORE',
                'include-category' => 'category',
                'section-identifier' => null,
                'exclude-category' => null,
                'weight-identifier' => null,
                'base-type' => null,
            ],
            $testVariables->attributes(),
        );
    }

    #[Test]
    public function itemSubsetAndValueSelectorsAreCarriedAsAttributes(): void
    {
        $testVariables = new TestVariables('SCORE', null, 'S1', 'skip', 'W', 'float');

        $this->assertSame(
            [
                'variable-identifier' => 'SCORE',
                'include-category' => null,
                'section-identifier' => 'S1',
                'exclude-category' => 'skip',
                'weight-identifier' => 'W',
                'base-type' => 'float',
            ],
            $testVariables->attributes(),
        );
    }
}
