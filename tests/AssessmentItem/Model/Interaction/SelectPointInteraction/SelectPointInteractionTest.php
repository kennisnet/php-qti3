<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\SelectPointInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\Prompt;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\SelectPointInteraction\SelectPointInteraction;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\HTMLTag;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SelectPointInteractionTest extends TestCase
{
    private SelectPointInteraction $interaction;
    private Prompt $prompt;
    private HTMLTag $image;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prompt = new Prompt(new ContentNodeCollection([new TextNode('test-prompt')]));
        $this->image = new HTMLTag('img', ['src' => 'test-image', 'alt' => 'test-alt']);

        $this->interaction = new SelectPointInteraction(
            image: $this->image,
            maxChoices: 1,
            prompt: $this->prompt,
            responseIdentifier: 'RESPONSE',
        );
    }

    #[Test]
    public function testAttributesAndChildren(): void
    {
        $this->assertEquals(
            [
                'max-choices' => '1',
                'response-identifier' => 'RESPONSE',
            ],
            $this->interaction->attributes(),
        );

        $this->assertEquals(
            [
                $this->prompt,
                $this->image,
            ],
            $this->interaction->children(),
        );
    }
}
