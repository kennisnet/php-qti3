<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Shared\Xml\Builder;

use DOMDocument;
use Qti3\Shared\Xml\Builder\RecursiveXMLSerializer;
use Qti3\Shared\Model\Comment;
use Qti3\Shared\Model\IXmlElement;
use Qti3\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RecursiveXMLSerializerTest extends TestCase
{
    #[Test]
    public function serializeWithCommentCreatesXmlComment(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $serializer = new RecursiveXMLSerializer($dom);

        $comment = new Comment('This is a comment');
        $serializer->serialize($comment);

        $this->assertSame(1, $dom->childNodes->length);
        $this->assertSame(XML_COMMENT_NODE, $dom->childNodes->item(0)->nodeType);
        $this->assertSame('This is a comment', $dom->childNodes->item(0)->textContent);
    }

    #[Test]
    public function serializeWithTextNodeCreatesTextNode(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElement('root');
        $dom->appendChild($root);

        $serializer = new RecursiveXMLSerializer($dom);

        $textNode = new TextNode('Some text content');
        $serializer->serialize($textNode, $root);

        $this->assertSame(1, $root->childNodes->length);
        $this->assertSame(XML_TEXT_NODE, $root->childNodes->item(0)->nodeType);
        $this->assertSame('Some text content', $root->childNodes->item(0)->textContent);
    }

    #[Test]
    public function serializeWithIXmlElementCreatesElement(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $serializer = new RecursiveXMLSerializer($dom);

        $element = $this->createMock(IXmlElement::class);
        $element->method('tagName')->willReturn('test-element');
        $element->method('attributes')->willReturn(['id' => 'abc', 'class' => 'test']);
        $element->method('children')->willReturn([]);

        $serializer->serialize($element);

        $this->assertSame(1, $dom->childNodes->length);
        $this->assertSame('test-element', $dom->childNodes->item(0)->tagName);
        $this->assertSame('abc', $dom->childNodes->item(0)->getAttribute('id'));
        $this->assertSame('test', $dom->childNodes->item(0)->getAttribute('class'));
    }

    /**
     * FLEX-692: attribute values must be escaped exactly once. Before the fix
     * htmlentities() ran before setAttribute(), which escapes itself, so
     * "&" became "&amp;amp;" and accented characters were mangled into
     * literal entities.
     */
    #[Test]
    public function serializeEscapesAttributeValuesExactlyOnce(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $serializer = new RecursiveXMLSerializer($dom);

        $element = $this->createMock(IXmlElement::class);
        $element->method('tagName')->willReturn('img');
        $element->method('attributes')->willReturn(['alt' => 'Kat & hond in een café']);
        $element->method('children')->willReturn([]);

        $serializer->serialize($element);

        $node = $dom->childNodes->item(0);
        $this->assertSame('Kat & hond in een café', $node->getAttribute('alt'));
        $this->assertStringContainsString('alt="Kat &amp; hond in een café"', $dom->saveXML());
    }

    #[Test]
    public function serializeKeepsMarkupCharactersInAttributeValuesIntact(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $serializer = new RecursiveXMLSerializer($dom);

        $element = $this->createMock(IXmlElement::class);
        $element->method('tagName')->willReturn('test-element');
        $element->method('attributes')->willReturn(['title' => 'a & b < c > d " e \' f']);
        $element->method('children')->willReturn([]);

        $serializer->serialize($element);

        $this->assertSame('a & b < c > d " e \' f', $dom->childNodes->item(0)->getAttribute('title'));
    }

    /**
     * FLEX-692: re-serializing an already-serialized value must be stable —
     * the double escaping grew the value on every autosave cycle.
     */
    #[Test]
    public function serializeIsStableAcrossRepeatedCycles(): void
    {
        $value = 'Kat & hond in een café';

        foreach ([1, 2, 3] as $ignored) {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $serializer = new RecursiveXMLSerializer($dom);

            $element = $this->createMock(IXmlElement::class);
            $element->method('tagName')->willReturn('img');
            $element->method('attributes')->willReturn(['alt' => $value]);
            $element->method('children')->willReturn([]);

            $serializer->serialize($element);

            $reparsed = new DOMDocument('1.0', 'UTF-8');
            $reparsed->loadXML($dom->saveXML());
            $value = $reparsed->documentElement->getAttribute('alt');
        }

        $this->assertSame('Kat & hond in een café', $value);
    }

    #[Test]
    public function serializeEscapesTextNodeContentExactlyOnce(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElement('p');
        $dom->appendChild($root);

        $serializer = new RecursiveXMLSerializer($dom);
        $serializer->serialize(new TextNode('Tekst met & en een café'), $root);

        $this->assertSame('Tekst met & en een café', $root->textContent);
        $this->assertStringContainsString('<p>Tekst met &amp; en een café</p>', $dom->saveXML());
    }
}
