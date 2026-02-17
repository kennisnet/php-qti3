<?php

declare(strict_types=1);

namespace Qti3\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use Qti3\Shared\Model\BaseType;
use Qti3\Shared\Model\Cardinality;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\TextNode;

#[Group('integration')]
class ItemParserIntegrationTest extends TestCase
{
    use QtiClientTestCaseTrait;

    protected function setUp(): void
    {
        $this->setUpQtiClientTestCase();
    }

    protected function tearDown(): void
    {
        $this->tearDownQtiClientTestCase();
    }

    public function testParseAssessmentItemFromXml(): void
    {
        $xml = <<<XML
<qti-assessment-item xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" 
                    identifier="item-001" 
                    title="Test Item" 
                    adaptive="false" 
                    time-dependent="false">
    <qti-response-declaration identifier="RESPONSE" cardinality="single" base-type="identifier">
        <qti-correct-response>
            <qti-value>choiceA</qti-value>
        </qti-correct-response>
    </qti-response-declaration>
    <qti-outcome-declaration identifier="SCORE" cardinality="single" base-type="float" />
    <qti-item-body>
        <p>Wat is de hoofdstad van Nederland?</p>
        <div>Extra info</div>
    </qti-item-body>
    <qti-response-processing template="https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/match_correct.xml" />
</qti-assessment-item>
XML;

        $client = $this->createClient();
        $xmlReader = $client->getXmlReader();
        $dom = $xmlReader->read($xml);
        
        $parser = $client->getAssessmentItemParser();
        $assessmentItem = $parser->parse($dom->documentElement);

        $this->assertInstanceOf(AssessmentItem::class, $assessmentItem);
        $this->assertSame('item-001', (string) $assessmentItem->identifier);
        $this->assertSame('Test Item', $assessmentItem->title);

        // Response Declaration
        $this->assertCount(1, $assessmentItem->responseDeclarations);
        /** @var ResponseDeclaration $responseDecl */
        $responseDecl = $assessmentItem->responseDeclarations->all()[0];
        $this->assertSame('RESPONSE', $responseDecl->identifier);
        $this->assertSame(Cardinality::SINGLE, $responseDecl->cardinality);
        $this->assertSame(BaseType::IDENTIFIER, $responseDecl->baseType);
        $this->assertSame('choiceA', (string)$responseDecl->correctResponse->values[0]);

        // Outcome Declaration
        $this->assertCount(1, $assessmentItem->outcomeDeclarations);
        $outcomeDecl = $assessmentItem->outcomeDeclarations->all()[0];
        $this->assertSame('SCORE', $outcomeDecl->identifier);

        // Item Body
        $itemBody = $assessmentItem->itemBody;
        $this->assertCount(2, $itemBody->content);
        
        /** @var HTMLTag $p */
        $p = $itemBody->content->all()[0];
        $this->assertInstanceOf(HTMLTag::class, $p);
        $this->assertSame('p', $p->tagName());
        $this->assertInstanceOf(TextNode::class, $p->children()[0]);
        $this->assertSame('Wat is de hoofdstad van Nederland?', (string)$p->children()[0]);

        /** @var HTMLTag $div */
        $div = $itemBody->content->all()[1];
        $this->assertInstanceOf(HTMLTag::class, $div);
        $this->assertSame('div', $div->tagName());
        $this->assertSame('Extra info', (string)$div->children()[0]);

        // Response Processing
        $this->assertNotNull($assessmentItem->responseProcessing);
    }
}
