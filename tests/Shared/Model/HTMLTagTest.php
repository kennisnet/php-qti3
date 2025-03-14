<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model;

use App\SharedKernel\Domain\Qti\Shared\Model\HTMLTag;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiResource;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HTMLTagTest extends TestCase
{
    private HTMLTag $tagWithName;
    private HTMLTag $tagWithAttributes;
    private HTMLTag $tagWithChildren;
    private HTMLTag $tagWithAttributesAndChildren;

    protected function setUp(): void
    {
        $this->tagWithName = new HTMLTag('hr');
        $this->tagWithAttributes = new HTMLTag('hr', ['class' => 'color: #ffffff;']);
        $this->tagWithChildren = new HTMLTag('hr', [], [new HTMLTag('p')]);
        $this->tagWithAttributesAndChildren = new HTMLTag('hr', ['class' => 'color: #ffffff;'], [new HTMLTag('p')]);
    }

    #[Test]
    public function aHTMLTagCanBeCreatedWithTagName(): void
    {
        $this->assertEquals('hr', $this->tagWithName->tagName());
        $this->assertEquals(true, $this->tagWithName->isBinary());
    }

    #[Test]
    public function aHTMLTagCanBeCreatedWithAttributes(): void
    {
        $this->assertEquals(['class' => 'color: #ffffff;'], $this->tagWithAttributes->attributes());
    }

    #[Test]
    public function aHTMLTagCanBeCreatedWithChildren(): void
    {
        $this->assertEquals('p', $this->tagWithChildren->children()[0]->tagName());
    }

    #[Test]
    public function aHTMLTagCanBeCreatedWithAttributesAndChildren(): void
    {
        $this->assertEquals(['class' => 'color: #ffffff;'], $this->tagWithAttributesAndChildren->attributes());
        $this->assertEquals('p', $this->tagWithAttributesAndChildren->children()[0]->tagName());
    }

    #[Test]
    public function aHTMLTagCannotBeCreatedWithInvalidTagName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HTMLTag('hr-');
    }

    #[Test]
    public function aHTMLTagCannotBeCreatedWithInvalidAttributes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HTMLTag('hr', ['href' => 'https://www.example.com']);
    }

    #[Test]
    public function aHTMLTagCannotBeCreatedWithoutRequiredAttributes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HTMLTag('a');
    }

    #[Test]
    public function aResourceCanBeSet(): void
    {
        $tag = new HTMLTag('img', ['src' => 'https://www.example.com/image.jpg', 'alt' => '']);
        $tag->setResource(new QtiResource('webcontent', 'https://www.example.com/image.jpg', '', 'image.jpg'));
        $this->assertEquals('image.jpg', $tag->attributes()['src']);
    }
}
