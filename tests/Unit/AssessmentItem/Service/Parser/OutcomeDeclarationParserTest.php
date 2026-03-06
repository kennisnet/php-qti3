<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Service\Parser;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use Qti3\Shared\Model\BaseType;
use Qti3\Shared\Model\Cardinality;

class OutcomeDeclarationParserTest extends TestCase
{
    private OutcomeDeclarationParser $parser;

    protected function setUp(): void
    {
        $this->parser = new OutcomeDeclarationParser();
    }

    private function loadElement(string $xml): DOMElement
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc->documentElement;
    }

    #[Test]
    public function parseReturnsOutcomeDeclarationWithCorrectAttributes(): void
    {
        $element = $this->loadElement('
            <qti-outcome-declaration identifier="SCORE" base-type="float" cardinality="single"/>
        ');

        $result = $this->parser->parse($element);

        $this->assertSame('SCORE', $result->identifier);
        $this->assertSame(BaseType::FLOAT, $result->baseType);
        $this->assertSame(Cardinality::SINGLE, $result->cardinality);
        $this->assertNull($result->defaultValue);
    }

    #[Test]
    public function parseExtractsDefaultValue(): void
    {
        $element = $this->loadElement('
            <qti-outcome-declaration identifier="PASS" base-type="boolean" cardinality="single">
                <qti-default-value>
                    <qti-value>false</qti-value>
                </qti-default-value>
            </qti-outcome-declaration>
        ');

        $result = $this->parser->parse($element);

        $this->assertNotNull($result->defaultValue);
        $this->assertSame('false', (string) $result->defaultValue->value);
    }

    #[Test]
    public function emptyQtiValueTreatedAsNoDefault(): void
    {
        $element = $this->loadElement('
            <qti-outcome-declaration identifier="PASS" base-type="boolean" cardinality="single">
                <qti-default-value>
                    <qti-value></qti-value>
                </qti-default-value>
            </qti-outcome-declaration>
        ');

        $result = $this->parser->parse($element);

        $this->assertNull($result->defaultValue);
    }

    #[Test]
    public function missingDefaultValueElementReturnsNull(): void
    {
        $element = $this->loadElement('
            <qti-outcome-declaration identifier="SCORE" base-type="float" cardinality="single"/>
        ');

        $result = $this->parser->parse($element);

        $this->assertNull($result->defaultValue);
    }
}
