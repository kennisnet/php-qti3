<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiTagName;
use App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\ExtendedTextInteraction\ExtendedTextInteractionStub;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QtiTagNameTest extends TestCase
{
    private QtiTagName $tagName;

    protected function setUp(): void
    {
        $textInteraction = new ExtendedTextInteractionStub();
        $this->tagName = new QtiTagName($textInteraction::class);
    }

    #[Test]
    public function aQTITagNameConvertsAClassnameToQTIElementTagName(): void
    {
        $this->assertEquals('qti-extended-text-interaction-stub', (string) $this->tagName);
    }
}
