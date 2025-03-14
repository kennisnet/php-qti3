<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItemId;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AssessmentItemRefTest extends TestCase
{
    private AssessmentItemRef $itemRef;

    protected function setUp(): void
    {
        parent::setUp();
        $this->itemRef = new AssessmentItemRef(
            'ITEM001',
            'ITEM001.xml',
            AssessmentItemId::fromQuestionnaire(1, 0),
        );
    }

    #[Test]
    public function testAttributes(): void
    {
        $this->assertEquals([
            'identifier' => 'ITEM001',
            'href' => 'ITEM001.xml',
            'category' => null,
        ], $this->itemRef->attributes());
    }
}
