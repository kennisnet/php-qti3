<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\GapMatchInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\GapMatchInteraction\Gap;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\GapMatchInteraction\GapMatchInteraction;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\GapMatchInteraction\GapText;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\Prompt;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\HTMLTag;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GapMatchInteractionTest extends TestCase
{
    private GapMatchInteraction $gapMatchInteraction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gapMatchInteraction = new GapMatchInteraction(
            content: new ContentNodeCollection([
                new GapText('gap1', 1, new ContentNodeCollection([new TextNode('Hello')])),
                new GapText('gap2', 1, new ContentNodeCollection([new TextNode('World')])),
                new HTMLTag('p', [], [
                    new TextNode('What is the first printed text in software'),
                    new Gap('gap1'),
                    new Gap('gap2'),
                ]),
            ]),
            responseIdentifier: 'RESPONSE',
            prompt: new Prompt(new ContentNodeCollection([new TextNode('Prompt')])),
        );
    }

    #[Test]
    public function aInstanceOfTheInteractionCanBeMade(): void
    {
        $this->assertEquals([
            'response-identifier' => 'RESPONSE',
            'shuffle' => 'false',
            'max-associations' => 0,
            'min-associations' => null,
            'class' => null,
        ], $this->gapMatchInteraction->attributes());

        $this->assertInstanceOf(HTMLTag::class, $this->gapMatchInteraction->children()[3]);
        $this->assertEquals('gap2', $this->gapMatchInteraction->children()[2]->attributes()['identifier']);
        $this->assertEquals('gap1', $this->gapMatchInteraction->children()[3]->children()[1]->attributes()['identifier']);
        $this->assertInstanceOf(GapMatchInteraction::class, $this->gapMatchInteraction);
    }

    #[Test]
    public function anInstanceOfTheInteractionCanNotBeMade(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GapMatchInteraction(
            content: new ContentNodeCollection(),
            responseIdentifier: 'RESPONSE',
            prompt: new Prompt(new ContentNodeCollection([new TextNode('Prompt')])),
        );
    }

    #[Test]
    public function thatNotAllTagsAreAllowed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GapMatchInteraction(
            content: new ContentNodeCollection([new HTMLTag('mtext', [])]),
            responseIdentifier: 'RESPONSE',
            prompt: new Prompt(new ContentNodeCollection([new TextNode('Prompt')])),
        );
    }
}
