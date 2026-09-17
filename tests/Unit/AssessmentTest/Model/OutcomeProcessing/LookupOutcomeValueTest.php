<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentTest\Model\OutcomeProcessing;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentTest\Model\OutcomeProcessing\ExitTest;
use Qti3\AssessmentTest\Model\OutcomeProcessing\IOutcomeProcessingElement;
use Qti3\AssessmentTest\Model\OutcomeProcessing\LookupOutcomeValue;
use Qti3\Shared\Model\Processing\Variable;

final class LookupOutcomeValueTest extends TestCase
{
    #[Test]
    public function carriesItsIdentifierAndExpression(): void
    {
        $lookup = new LookupOutcomeValue('GRADE', new Variable('SCORE'));

        $this->assertInstanceOf(IOutcomeProcessingElement::class, $lookup);
        $this->assertSame('qti-lookup-outcome-value', $lookup->tagName());
        $this->assertSame(['identifier' => 'GRADE'], $lookup->attributes());
        $this->assertInstanceOf(Variable::class, $lookup->children()[0]);
    }

    #[Test]
    public function exitTestIsAnEmptyRule(): void
    {
        $exit = new ExitTest();

        $this->assertInstanceOf(IOutcomeProcessingElement::class, $exit);
        $this->assertSame('qti-exit-test', $exit->tagName());
        $this->assertSame([], $exit->attributes());
        $this->assertSame([], $exit->children());
    }
}
