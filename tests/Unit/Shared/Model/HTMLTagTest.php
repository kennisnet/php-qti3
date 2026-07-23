<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Shared\Model;

use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\QtiResource;
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

    #[Test]
    public function aMathMLTagCanBeCreatedWithAnyAttribute(): void
    {
        $tag = new HTMLTag('math', ['xmlns' => 'http://www.w3.org/1998/Math/MathML']);
        $this->assertEquals('math', $tag->tagName());
        $this->assertEquals(['xmlns' => 'http://www.w3.org/1998/Math/MathML'], $tag->attributes());
    }

    #[Test]
    public function attributeIsValidReturnsTrueForMathMLTags(): void
    {
        $this->assertTrue(HTMLTag::attributeIsValid('math', 'xmlns', 'http://www.w3.org/1998/Math/MathML'));
        $this->assertTrue(HTMLTag::attributeIsValid('mfrac', 'linethickness', '2'));
        $this->assertTrue(HTMLTag::attributeIsValid('mo', 'stretchy', 'true'));
    }

    #[Test]
    public function booleanAttributeWithTrueValueIsValid(): void
    {
        $tag = new HTMLTag('audio', ['controls' => 'true']);
        $this->assertEquals(['controls' => 'true'], $tag->attributes());
    }

    #[Test]
    public function booleanAttributeWithFalseValueIsValid(): void
    {
        $tag = new HTMLTag('audio', ['controls' => 'false']);
        $this->assertEquals(['controls' => 'false'], $tag->attributes());
    }

    #[Test]
    public function booleanAttributeWithInvalidValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HTMLTag('audio', ['controls' => 'yes']);
    }

    #[Test]
    public function getBlockTagsReturnsBlockTags(): void
    {
        $blockTags = HTMLTag::getBlockTags();
        $this->assertIsArray($blockTags);
        $this->assertContains('audio', $blockTags);
        $this->assertContains('div', $blockTags);
        $this->assertContains('table', $blockTags);
        $this->assertContains('video', $blockTags);
        $this->assertNotContains('a', $blockTags);
        $this->assertNotContains('span', $blockTags);
    }

    #[Test]
    public function getSourceReturnsNullWhenNoSrcAttribute(): void
    {
        $tag = new HTMLTag('div');
        $this->assertNull($tag->getSource());
    }

    #[Test]
    public function getResourceReturnsNullWhenNoResourceSet(): void
    {
        $tag = new HTMLTag('div');
        $this->assertNull($tag->getResource());
    }

    #[Test]
    public function getSourceReturnsSrcsetUrlForASourceWithoutSrc(): void
    {
        $tag = new HTMLTag('source', ['srcset' => 'map.webp', 'type' => 'image/webp']);

        $this->assertSame('map.webp', $tag->getSource());
    }

    #[Test]
    public function getSourceReturnsTheFirstSrcsetUrlStrippingItsDescriptor(): void
    {
        $tag = new HTMLTag('source', ['srcset' => 'map.webp 2x, map-1x.webp 1x']);

        $this->assertSame('map.webp', $tag->getSource());
    }

    #[Test]
    public function aResolvedResourceRewritesTheSrcsetOfASourceElement(): void
    {
        $tag = new HTMLTag('source', ['srcset' => 'map.webp 2x', 'type' => 'image/webp']);

        $tag->setResource(new QtiResource('webcontent', 'map.webp', 'resources/', 'abc.webp'));

        // The srcset URL is replaced with the bundled path; the descriptor is
        // preserved, and no stray `src` attribute is introduced.
        $this->assertSame('resources/abc.webp 2x', $tag->attributes()['srcset']);
        $this->assertArrayNotHasKey('src', $tag->attributes());
    }

    #[Test]
    public function aSourceElementWithoutSrcOrSrcsetThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HTMLTag('source', ['type' => 'image/webp']);
    }

    #[Test]
    public function anImgWithoutAltIsAcceptedAndSerializesWithAnEmptyAlt(): void
    {
        // Authoring an <img> without alt no longer throws; a default empty
        // alt="" is emitted so the serialized element stays valid.
        $tag = new HTMLTag('img', ['src' => 'resources/pic.png']);

        $this->assertSame('', $tag->attributes()['alt']);
    }

    #[Test]
    public function anImgKeepsAnExplicitAlt(): void
    {
        $tag = new HTMLTag('img', ['src' => 'resources/pic.png', 'alt' => 'A cat']);

        $this->assertSame('A cat', $tag->attributes()['alt']);
    }

    #[Test]
    public function anImgStillRequiresSrc(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HTMLTag('img', ['alt' => 'A cat']);
    }
}
