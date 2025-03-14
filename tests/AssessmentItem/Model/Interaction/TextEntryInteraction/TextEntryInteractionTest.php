<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\TextEntryInteraction;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\TextEntryInteraction\TextEntryInteraction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TextEntryInteractionTest extends TestCase
{
    private TextEntryInteraction $textEntryInteraction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->textEntryInteraction = new TextEntryInteraction();
    }

    #[Test]
    public function textEntryInteractionCanBeCreated(): void
    {
        $this->assertInstanceOf(TextEntryInteraction::class, $this->textEntryInteraction);
    }

    #[Test]
    public function textEntryInteractionCanBeCreatedWithResponseIdentifier(): void
    {
        $textEntryInteractionWithResponse = new TextEntryInteraction('RESPONSE2');
        $this->assertEquals(['response-identifier' => 'RESPONSE2'], $textEntryInteractionWithResponse->attributes());
    }
}
