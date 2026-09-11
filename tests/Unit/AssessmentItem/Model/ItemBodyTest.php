<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Model;

use Qti3\AssessmentItem\Model\ItemBody;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\HTMLTag;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ItemBodyTest extends TestCase
{
    private ItemBody $itemBody;

    protected function setUp(): void
    {
        parent::setUp();
        $this->itemBody = new ItemBody(new ContentNodeCollection([
            new HTMLTag('p'),
        ]));
    }

    #[Test]
    public function anItemBodyCanBeCreated(): void
    {
        $this->assertCount(1, $this->itemBody->children());
        $this->assertEquals([], $this->itemBody->attributes());
    }

    #[Test]
    public function anItemBodyCanBeCreatedWithChildren(): void
    {
        $itemBodyWithChildren = new ItemBody(new ContentNodeCollection([new HTMLTag('p')]));
        $this->assertEquals('p', $itemBodyWithChildren->children()[0]->tagName());
    }

    #[Test]
    public function anItemBodyWithNoChildrenThrowsAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ItemBody(new ContentNodeCollection());
    }

    #[Test]
    public function rejectsAnInlineTagAsDirectChild(): void
    {
        $this->assertFalse(ItemBody::allowsAsDirectChild('strong'));
    }

    /** `ItemBodyDType` lists `m3:math`, but not the elements inside it. */
    #[Test]
    public function acceptsAMathMlRootButNotItsInnerElements(): void
    {
        $this->assertTrue(ItemBody::allowsAsDirectChild('math'));
        $this->assertFalse(ItemBody::allowsAsDirectChild('mi'));
    }

    /**
     * A package reaches this library without having been schema-validated, so
     * reading one must not fail over a tag out of place; the check above is for
     * the entry points that author content.
     */
    #[Test]
    public function constructorAcceptsATagThatIsNotBlockContent(): void
    {
        $itemBody = new ItemBody(new ContentNodeCollection([new HTMLTag('strong')]));

        $this->assertSame('strong', $itemBody->children()[0]->tagName());
    }
}
