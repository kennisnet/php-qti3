<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Exception\InvalidAssessmentItemException;
use Qti3\AssessmentItem\Service\AssessmentItemValidator;
use Qti3\Shared\Xml\Reader\XmlReader;

final class AssessmentItemValidatorTest extends TestCase
{
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';

    private AssessmentItemValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AssessmentItemValidator(new XmlReader());
    }

    #[Test]
    public function itAcceptsAValidChoiceItem(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validate($this->item('ITEM001', '<qti-item-body/>'));
    }

    #[Test]
    public function itAcceptsAnInlineChoiceItemWhichTheTypedParserCannotHandle(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validate($this->item(
            'ITEM002',
            '<qti-item-body><qti-inline-choice-interaction response-identifier="RESPONSE">'
            . '<qti-inline-choice identifier="A">A</qti-inline-choice>'
            . '</qti-inline-choice-interaction></qti-item-body>',
        ));
    }

    #[Test]
    public function itRejectsAnEmptyString(): void
    {
        $this->assertValidationErrors('   ', ['Item XML is empty']);
    }

    #[Test]
    public function itRejectsMalformedXml(): void
    {
        try {
            $this->validator->validate('<qti-assessment-item');
            $this->fail('Expected InvalidAssessmentItemException');
        } catch (InvalidAssessmentItemException $exception) {
            $this->assertStringContainsString('Invalid XML', $exception->validationErrors()->join("\n"));
        }
    }

    #[Test]
    public function itRejectsAWrongRootElement(): void
    {
        $xml = sprintf(
            '<qti-assessment-test xmlns="%s" identifier="X" title="T" time-dependent="false"/>',
            self::ASI_NAMESPACE,
        );

        $this->assertValidationErrors($xml, ['Root element must be qti-assessment-item, found: qti-assessment-test']);
    }

    #[Test]
    public function itRejectsAWrongNamespace(): void
    {
        $this->assertValidationErrors(
            '<qti-assessment-item identifier="X" title="T" time-dependent="false"/>',
            ['Invalid namespace: none, expected: ' . self::ASI_NAMESPACE],
        );
    }

    #[Test]
    public function itReportsEachMissingRequiredAttribute(): void
    {
        $this->assertValidationErrors(
            sprintf('<qti-assessment-item xmlns="%s"/>', self::ASI_NAMESPACE),
            [
                'Missing required attribute: identifier',
                'Missing required attribute: title',
                'Missing required attribute: time-dependent',
            ],
        );
    }

    #[Test]
    public function itRejectsAnEmptyRequiredAttributeValue(): void
    {
        $xml = sprintf(
            '<qti-assessment-item xmlns="%s" identifier="" title="T" time-dependent="false"/>',
            self::ASI_NAMESPACE,
        );

        $this->assertValidationErrors($xml, ['Missing required attribute: identifier']);
    }

    /**
     * @param list<string> $expectedErrors
     */
    private function assertValidationErrors(string $xml, array $expectedErrors): void
    {
        try {
            $this->validator->validate($xml);
            $this->fail('Expected InvalidAssessmentItemException');
        } catch (InvalidAssessmentItemException $exception) {
            $this->assertSame($expectedErrors, $exception->validationErrors()->all());
        }
    }

    private function item(string $identifier, string $body): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<qti-assessment-item xmlns="%s" identifier="%s" title="Vraag" time-dependent="false">%s</qti-assessment-item>',
            self::ASI_NAMESPACE,
            $identifier,
            $body,
        );
    }
}
