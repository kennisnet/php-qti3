<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Model\OutcomeProcessing;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentTest\Model\OutcomeProcessing\ExitTest;
use Qti3\AssessmentTest\Model\OutcomeProcessing\IOutcomeProcessingElement;

final class ExitTestTest extends TestCase
{
    #[Test]
    public function isAnEmptyRule(): void
    {
        $exit = new ExitTest();

        $this->assertInstanceOf(IOutcomeProcessingElement::class, $exit);
        $this->assertSame('qti-exit-test', $exit->tagName());
        $this->assertSame([], $exit->attributes());
        $this->assertSame([], $exit->children());
    }
}
