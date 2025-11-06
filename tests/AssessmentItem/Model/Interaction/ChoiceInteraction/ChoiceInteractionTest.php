<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\ChoiceInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\ChoiceInteraction\ChoiceInteraction;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\ChoiceInteraction\SimpleChoice;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ChoiceInteractionTest extends TestCase
{
    private ChoiceInteraction $choiceInteraction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->choiceInteraction = new ChoiceInteraction(
            [
                new SimpleChoice('A', new ContentNodeCollection([new TextNode('Antwoord 1')])),
                new SimpleChoice('B', new ContentNodeCollection([new TextNode('Antwoord 2')])),
            ],
        );
    }

    #[Test]
    public function aChoiceInteractionCanBeCreated(): void
    {
        $this->assertInstanceOf(ChoiceInteraction::class, $this->choiceInteraction);
        $this->assertEquals('RESPONSE', $this->choiceInteraction->attributes()['response-identifier']);
        $this->assertCount(3, $this->choiceInteraction->children());
        $this->assertEquals('A', $this->choiceInteraction->children()[1]->attributes()['identifier']);
        $this->assertInstanceOf(TextNode::class, $this->choiceInteraction->children()[1]->children()[0]);
    }
}
