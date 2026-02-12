<?php

declare(strict_types=1);

namespace Qti3\Tests\AssessmentItem\Service;

use Qti3\AssessmentItem\Service\AssessmentItemDeterminator;
use DOMDocument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AssessmentItemDeterminatorTest extends TestCase
{
    private AssessmentItemDeterminator $determinator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->determinator = new AssessmentItemDeterminator();
    }

    #[Test]
    public function determineManualScoringReturnsFalseWhenNoExtendedTextInteraction(): void
    {
        $xml = new DOMDocument();
        $xml->loadXML('<qti-assessment-item><qti-item-body></qti-item-body></qti-assessment-item>');

        $this->assertFalse($this->determinator->determineManualScoring($xml));
    }

    #[Test]
    public function determineManualScoringReturnsTrueWhenExtendedTextInteractionPresent(): void
    {
        $xml = new DOMDocument();
        $xml->loadXML('<qti-assessment-item><qti-item-body><qti-extended-text-interaction response-identifier="RESPONSE"/></qti-item-body></qti-assessment-item>');

        $this->assertTrue($this->determinator->determineManualScoring($xml));
    }

    #[Test]
    public function determineTypeReturnsQuestionWhenResponseProcessingPresent(): void
    {
        $xml = new DOMDocument();
        $xml->loadXML('<qti-assessment-item><qti-response-processing/></qti-assessment-item>');

        $this->assertEquals('question', $this->determinator->determineType($xml));
    }

    #[Test]
    public function determineTypeReturnsQuestionWhenExtendedTextInteractionPresent(): void
    {
        $xml = new DOMDocument();
        $xml->loadXML('<qti-assessment-item><qti-extended-text-interaction response-identifier="RESPONSE"/></qti-assessment-item>');

        $this->assertEquals('question', $this->determinator->determineType($xml));
    }

    #[Test]
    public function determineTypeReturnsInfoWhenNeitherPresent(): void
    {
        $xml = new DOMDocument();
        $xml->loadXML('<qti-assessment-item><qti-item-body></qti-item-body></qti-assessment-item>');

        $this->assertEquals('info', $this->determinator->determineType($xml));
    }

    #[Test]
    public function determineTitleReturnsTheTitleAttribute(): void
    {
        $xml = new DOMDocument();
        $xml->loadXML('<qti-assessment-item title="My Test Title"></qti-assessment-item>');

        $this->assertEquals('My Test Title', $this->determinator->determineTitle($xml));
    }

    #[Test]
    public function determineTitleReturnsEmptyStringWhenNoTitle(): void
    {
        $xml = new DOMDocument();
        $xml->loadXML('<qti-assessment-item></qti-assessment-item>');

        $this->assertEquals('', $this->determinator->determineTitle($xml));
    }
}
