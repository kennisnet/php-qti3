<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\RubricBlock;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\RubricBlock\qtiUse;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\RubricBlock\RubricBlock;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\RubricBlock\View;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentBody;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RubricBlockTest extends TestCase
{
    private RubricBlock $rubricBlock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rubricBlock = new RubricBlock(
            use: qtiUse::INSTRUCTIONS,
            view: View::TUTOR,
            contentBody: new ContentBody(new ContentNodeCollection([new TextNode('contentBody')])),
            class: 'test',
        );
    }

    #[Test]
    public function testRubricBlock(): void
    {
        $expectedAttributes = [
            'use' => 'instructions',
            'view' => 'tutor',
            'class' => 'test',
        ];

        $this->assertEquals($expectedAttributes, $this->rubricBlock->attributes());
        $this->assertInstanceOf(ContentBody::class, $this->rubricBlock->children()[0]);
    }
}
