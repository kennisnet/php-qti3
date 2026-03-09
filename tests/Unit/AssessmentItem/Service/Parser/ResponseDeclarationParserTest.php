<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Service\Parser;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use Qti3\AssessmentItem\Service\Parser\ParseError;
use Qti3\AssessmentItem\Service\Parser\ResponseDeclarationParser;
use Qti3\Shared\Model\BaseType;
use Qti3\Shared\Model\Cardinality;

class ResponseDeclarationParserTest extends TestCase
{
    private ResponseDeclarationParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ResponseDeclarationParser();
    }

    private function loadElement(string $xml): DOMElement
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc->documentElement;
    }

    #[Test]
    public function parseMappingBoundsFromMappingElement(): void
    {
        $element = $this->loadElement('
            <qti-response-declaration identifier="RESPONSE" cardinality="multiple" base-type="identifier">
                <qti-correct-response>
                    <qti-value>choiceA</qti-value>
                </qti-correct-response>
                <qti-mapping default-value="0.5" lower-bound="0" upper-bound="3">
                    <qti-map-entry map-key="choiceA" mapped-value="1" case-sensitive="false"/>
                    <qti-map-entry map-key="choiceB" mapped-value="-0.5" case-sensitive="true"/>
                </qti-mapping>
            </qti-response-declaration>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(ResponseDeclaration::class, $result);
        $this->assertSame('RESPONSE', $result->identifier);
        $this->assertSame(Cardinality::MULTIPLE, $result->cardinality);
        $this->assertSame(BaseType::IDENTIFIER, $result->baseType);

        // Verify mapping bounds are read from <qti-mapping>, not <qti-response-declaration>
        $this->assertNotNull($result->mapping);
        $this->assertSame(0.5, $result->mapping->defaultValue);
        $this->assertSame(0.0, $result->mapping->lowerBound);
        $this->assertSame(3.0, $result->mapping->upperBound);

        // Verify map entries
        $this->assertCount(2, $result->mapping->entries);
        $this->assertSame('choiceA', $result->mapping->entries[0]->mapKey);
        $this->assertSame(1.0, $result->mapping->entries[0]->mappedValue);
        $this->assertFalse($result->mapping->entries[0]->caseSensitive);
        $this->assertSame('choiceB', $result->mapping->entries[1]->mapKey);
        $this->assertSame(-0.5, $result->mapping->entries[1]->mappedValue);
        $this->assertTrue($result->mapping->entries[1]->caseSensitive);
    }

    #[Test]
    public function parseMappingBoundsAreNullWhenOmitted(): void
    {
        $element = $this->loadElement('
            <qti-response-declaration identifier="RESPONSE" cardinality="single" base-type="identifier">
                <qti-mapping>
                    <qti-map-entry map-key="A" mapped-value="1" case-sensitive="false"/>
                </qti-mapping>
            </qti-response-declaration>
        ');

        $result = $this->parser->parse($element);

        $this->assertNotNull($result->mapping);
        $this->assertNull($result->mapping->defaultValue);
        $this->assertNull($result->mapping->lowerBound);
        $this->assertNull($result->mapping->upperBound);
    }

    #[Test]
    public function parseWithoutMapping(): void
    {
        $element = $this->loadElement('
            <qti-response-declaration identifier="RESPONSE" cardinality="single" base-type="identifier">
                <qti-correct-response>
                    <qti-value>A</qti-value>
                </qti-correct-response>
            </qti-response-declaration>
        ');

        $result = $this->parser->parse($element);

        $this->assertSame('RESPONSE', $result->identifier);
        $this->assertNotNull($result->correctResponse);
        $this->assertCount(1, $result->correctResponse->values);
        $this->assertSame('A', (string) $result->correctResponse->values[0]);
        $this->assertNull($result->mapping);
    }

    #[Test]
    public function parseWrongTagThrows(): void
    {
        $element = $this->loadElement('<wrong-tag identifier="R" cardinality="single" base-type="string"/>');

        $this->expectException(ParseError::class);
        $this->expectExceptionMessage('Expected tag "qti-response-declaration", got "wrong-tag"');

        $this->parser->parse($element);
    }
}
