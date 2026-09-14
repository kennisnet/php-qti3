<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Shared\Html;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Shared\Xml\Builder\XmlBuilder;
use Qti3\Shared\Html\ContentNodeParser;
use Qti3\Shared\Html\HtmlFragmentParser;
use Qti3\Shared\Html\HtmlFragmentSerializer;
use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Model\ContentNodeCollection;

class HtmlFragmentSerializerTest extends TestCase
{
    private HtmlFragmentParser $parser;
    private HtmlFragmentSerializer $serializer;

    protected function setUp(): void
    {
        $this->parser = new HtmlFragmentParser(new ContentNodeParser());
        $this->serializer = new HtmlFragmentSerializer(new XmlBuilder());
    }

    #[Test]
    public function aFragmentRoundTripsThroughParseAndSerialize(): void
    {
        $html = '<p><strong>Lees</strong> dit</p><ol><li>Een</li><li>Twee</li></ol>';

        $result = $this->serializer->serialize($this->parser->parse($html));

        $this->assertSame($html, $result);
    }

    #[Test]
    public function aVoidElementIsSelfClosed(): void
    {
        $result = $this->serializer->serialize($this->parser->parse('<hr>'));

        $this->assertSame('<hr/>', $result);
    }

    #[Test]
    public function aNonBreakingSpaceIsWrittenAsARawCharacterNotAnEntity(): void
    {
        $result = $this->serializer->serialize($this->parser->parse('<p>a&nbsp;b</p>'));

        $this->assertSame("<p>a\u{00a0}b</p>", $result);
    }

    #[Test]
    public function anEmptyContentBodySerializesToAnEmptyString(): void
    {
        $result = $this->serializer->serialize(new ContentBody(new ContentNodeCollection()));

        $this->assertSame('', $result);
    }

    #[Test]
    public function serializeDoesNotReIndentTheOutput(): void
    {
        $html = '<p>a</p><p>b</p>';

        $result = $this->serializer->serialize($this->parser->parse($html));

        $this->assertStringNotContainsString("\n", $result);
    }
}
