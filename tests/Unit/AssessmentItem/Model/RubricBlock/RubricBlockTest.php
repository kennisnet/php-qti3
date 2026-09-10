<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Model\RubricBlock;

use InvalidArgumentException;
use Qti3\AssessmentItem\Model\RubricBlock\qtiUse;
use Qti3\AssessmentItem\Model\RubricBlock\RubricBlock;
use Qti3\AssessmentItem\Model\RubricBlock\View;
use Qti3\AssessmentItem\Model\RubricBlock\ViewCollection;
use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\TextNode;
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
            views: new ViewCollection([View::TUTOR]),
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

    #[Test]
    public function severalViewsAreJoinedByASpaceInSourceOrder(): void
    {
        $rubricBlock = new RubricBlock(
            use: qtiUse::SCORING,
            views: new ViewCollection([View::CANDIDATE, View::SCORER]),
            contentBody: $this->contentBody(),
        );

        $this->assertSame('candidate scorer', $rubricBlock->attributes()['view']);
    }

    #[Test]
    public function useIsOmittedWhenItIsNotSet(): void
    {
        $rubricBlock = new RubricBlock(
            use: null,
            views: new ViewCollection([View::CANDIDATE]),
            contentBody: $this->contentBody(),
        );

        $this->assertNull($rubricBlock->attributes()['use']);
    }

    #[Test]
    public function hasViewTellsWhetherTheBlockIsAddressedToAView(): void
    {
        $rubricBlock = new RubricBlock(
            use: null,
            views: new ViewCollection([View::CANDIDATE, View::SCORER]),
            contentBody: $this->contentBody(),
        );

        $this->assertTrue($rubricBlock->hasView(View::CANDIDATE));
        $this->assertTrue($rubricBlock->hasView(View::SCORER));
        $this->assertFalse($rubricBlock->hasView(View::TUTOR));
    }

    #[Test]
    public function aRubricBlockWithoutAViewIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least one view');

        new RubricBlock(
            use: qtiUse::INSTRUCTIONS,
            views: new ViewCollection(),
            contentBody: $this->contentBody(),
        );
    }

    private function contentBody(): ContentBody
    {
        return new ContentBody(new ContentNodeCollection([new TextNode('contentBody')]));
    }
}
