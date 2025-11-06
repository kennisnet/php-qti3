<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section\AssessmentSectionCollection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\NavigationMode;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\SubmissionMode;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\TestPart;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TestPartTest extends TestCase
{
    private TestPart $testPart;

    protected function setUp(): void
    {
        $this->testPart = new TestPart(
            'id',
            NavigationMode::LINEAR,
            SubmissionMode::INDIVIDUAL,
            new AssessmentSectionCollection(),
        );
    }

    #[Test]
    public function anIdentifierCanBeRetrieved(): void
    {
        $this->assertSame('id', $this->testPart->identifier);
    }
}
