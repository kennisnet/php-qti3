<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model\Feedback;

use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Feedback\TestFeedback;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentBody;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TestFeedbackTest extends TestCase
{
    private TestFeedback $testFeedback;

    protected function setUp(): void
    {
        $this->testFeedback = new TestFeedback(
            'feedback',
            'outcomeIdentifier',
            new ContentBody(new ContentNodeCollection([
                new TextNode('test'),
            ])),
        );
    }

    #[Test]
    public function testAttributes(): void
    {
        $this->assertEquals([
            'identifier' => 'feedback',
            'outcome-identifier' => 'outcomeIdentifier',
            'show-hide' => 'show',
            'access' => 'atEnd',
        ], $this->testFeedback->attributes());
    }

    #[Test]
    public function testChildren(): void
    {
        $this->assertInstanceOf(ContentBody::class, $this->testFeedback->children()[0]);
    }
}
