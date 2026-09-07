<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Validator;

use Qti3\AssessmentItem\Service\AssessmentItemDeterminator;
use Qti3\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use Qti3\AssessmentItem\Service\Parser\ProcessingElementParser;
use Qti3\AssessmentItem\Service\Parser\QtiExpressionParser;
use Qti3\AssessmentItem\Service\Parser\ResponseDeclarationParser;
use Qti3\AssessmentItem\Service\Parser\ResponseProcessingParser;
use Qti3\AssessmentItem\Service\ResponseProcessor;
use Qti3\AssessmentItem\Service\ScoringOutcomeValidator;
use Qti3\Package\Model\FileContent\MemoryFileContent;
use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\PackageFile\PackageFileCollection;
use Qti3\Package\Model\PackageFile\XmlFile;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Model\Resource\ResourceCollection;
use Qti3\Package\Model\Resource\ResourceType;
use Qti3\Package\Validator\QtiPackageValidationError;
use Qti3\Package\Validator\QtiPackageValidator;
use Qti3\Package\Validator\ResponseProcessingValidator;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Xml\Reader\XmlReader;
use Qti3\Tests\Unit\Package\Model\QtiPackageMock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ResponseProcessingValidatorTest extends TestCase
{
    private MockObject $responseProcessor;
    private ResponseProcessingValidator $validator;

    protected function setUp(): void
    {
        $this->responseProcessor = $this->createMock(ResponseProcessor::class);
        $this->validator = new ResponseProcessingValidator($this->responseProcessor);
    }

    #[Test]
    public function validateReturnsNoErrorsWhenItemProcessingSucceeds(): void
    {
        $qtiPackage = new QtiPackageMock();

        $errors = $this->validator->validate($qtiPackage);

        $this->assertCount(0, $errors);
    }

    #[Test]
    public function validateReturnsValidationErrorsFromQtiPackageValidationError(): void
    {
        $qtiPackage = new QtiPackageMock();

        $exception = new QtiPackageValidationError(new StringCollection(['Invalid responseProcessing']));

        $this->responseProcessor
            ->method('initItemState')
            ->willThrowException($exception);

        $errors = $this->validator->validate($qtiPackage);

        $this->assertEquals([
            'test-item1.xml: Invalid responseProcessing',
            'test-item2.xml: Invalid responseProcessing',
            'test-item3.xml: Invalid responseProcessing',
            'test-item4.xml: Invalid responseProcessing',
            'test-item5.xml: Invalid responseProcessing',
        ], iterator_to_array($errors));
    }

    #[Test]
    public function validateReturnsGenericExceptionMessage(): void
    {
        $qtiPackage = new QtiPackageMock();

        $this->responseProcessor
            ->method('initItemState')
            ->willThrowException(new RuntimeException('Unexpected error'));

        $errors = $this->validator->validate($qtiPackage);

        $this->assertEquals([
            'test-item1.xml: Unexpected error',
            'test-item2.xml: Unexpected error',
            'test-item3.xml: Unexpected error',
            'test-item4.xml: Unexpected error',
            'test-item5.xml: Unexpected error',
        ], iterator_to_array($errors));
    }

    /**
     * Acceptance criteria FLEX-599, through the full QtiPackageValidator chain:
     * a question item with response processing (inline/empty or template-based)
     * but without a SCORE outcome declaration is reported with its file path,
     * while items that declare SCORE add no error.
     */
    #[Test]
    public function validateReportsQuestionItemsWithoutScoreDeclaration(): void
    {
        $resources = __DIR__ . '/../../AssessmentItem/Service/resources/';
        $qtiPackage = new QtiPackageMock(new ResourceCollection([
            $this->itemResource('empty-processing.xml', file_get_contents($resources . 'missing-score-declaration-empty-processing.xml')),
            $this->itemResource('unknown-template.xml', file_get_contents($resources . 'missing-score-declaration-unknown-template.xml')),
            $this->itemResource('with-score.xml', file_get_contents($resources . 'match-correct-template.xml')),
        ]));

        $syntaxValidator = new NoopQtiSyntaxValidator();
        $validator = new QtiPackageValidator(
            $syntaxValidator,
            new ResponseProcessingValidator(new ResponseProcessor(
                new ResponseDeclarationParser(),
                new OutcomeDeclarationParser(),
                new ResponseProcessingParser(new ProcessingElementParser(new QtiExpressionParser())),
                new ScoringOutcomeValidator(new AssessmentItemDeterminator()),
            )),
        );

        $errors = $validator->validate($qtiPackage);

        $this->assertSame([
            'empty-processing.xml: Missing `qti-outcome-declaration` with identifier `SCORE`',
            'unknown-template.xml: Missing `qti-outcome-declaration` with identifier `SCORE`',
        ], iterator_to_array($errors));
    }

    private function itemResource(string $filepath, string $xml): Resource
    {
        return new Resource(
            pathinfo($filepath, PATHINFO_FILENAME),
            ResourceType::ASSESSMENT_ITEM,
            $filepath,
            new PackageFileCollection([
                new XmlFile($filepath, new MemoryFileContent($xml), new XmlReader()),
            ]),
            new ManifestResourceDependencyCollection(),
        );
    }
}
