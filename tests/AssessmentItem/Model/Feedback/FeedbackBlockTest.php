<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Feedback;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Feedback\FeedbackBlock;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentBody;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FeedbackBlockTest extends TestCase
{
    private FeedbackBlock $feedbackBlock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->feedbackBlock = new FeedbackBlock(
            identifier: 'OK',
            contentBody: new ContentBody(new ContentNodeCollection([new TextNode('Hierbij onze feedback')]))
        );
    }

    #[Test]
    public function aFeedbackBlockCanBeCreated(): void
    {
        $attributes = [
            'outcome-identifier' => 'FEEDBACK',
            'show-hide' => 'show',
            'identifier' => 'OK',
        ];

        $this->assertCount(1, $this->feedbackBlock->children());
        $this->assertEquals($attributes, $this->feedbackBlock->attributes());
    }
}
