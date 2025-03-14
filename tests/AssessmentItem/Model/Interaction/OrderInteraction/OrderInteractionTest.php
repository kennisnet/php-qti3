<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\OrderInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\ChoiceInteraction\SimpleChoice;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\OrderInteraction\OrderInteraction;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\OrderInteraction\Orientation;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\Prompt;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderInteractionTest extends TestCase
{
    private OrderInteraction $orderInteraction;
    private array $choices;
    private Prompt $prompt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->choices = [
            new SimpleChoice('choice1', new ContentNodeCollection([new TextNode('Choice 1')])),
            new SimpleChoice('choice2', new ContentNodeCollection([new TextNode('Choice 2')])),
        ];

        $this->prompt = new Prompt(new ContentNodeCollection([new TextNode('Prompt text')]));

        $this->orderInteraction = new OrderInteraction(
            choices: $this->choices,
            responseIdentifier: 'RESPONSE',
            orientation: Orientation::HORIZONTAL,
            shuffle: true,
            prompt: $this->prompt
        );
    }

    #[Test]
    public function testOrderInteraction(): void
    {
        $expectedAttributes = [
            'response-identifier' => 'RESPONSE',
            'orientation' => 'horizontal',
            'shuffle' => 'true',
        ];

        $this->assertSame($expectedAttributes, $this->orderInteraction->attributes());

        $expectedChildren = [
            $this->prompt,
            ...$this->choices,
        ];

        $this->assertSame($expectedChildren, $this->orderInteraction->children());
    }
}
