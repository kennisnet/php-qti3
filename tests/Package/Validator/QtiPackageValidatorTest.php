<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ProcessingElementParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\QtiExpressionParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ResponseDeclarationParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ResponseProcessingParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\ResponseProcessor;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\XmlFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use App\SharedKernel\Domain\Qti\Package\Validator\IImsQtiPackageValidator;
use App\SharedKernel\Domain\Qti\Package\Validator\QtiPackageValidator;
use App\SharedKernel\Domain\Qti\Package\Validator\ResponseProcessingValidator;
use App\SharedKernel\Domain\StringCollection;
use App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestMock;
use App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\QtiPackageMock;
use DOMDocument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QtiPackageValidatorTest extends TestCase
{
    private QtiPackageValidator $validator;

    public function setUp(): void
    {
        $imsQtiPackageValidator = $this->createMock(IIMSQtiPackageValidator::class);
        $imsQtiPackageValidator
            ->method('validate')->willReturn(new StringCollection([]));

        $this->validator = new QtiPackageValidator(
            $imsQtiPackageValidator,
            new ResponseProcessingValidator(
                new ResponseProcessor(
                    new ResponseDeclarationParser(),
                    new OutcomeDeclarationParser(),
                    new ResponseProcessingParser(
                        new ProcessingElementParser(
                            new QtiExpressionParser()
                        )
                    ),
                )
            )
        );
    }

    #[Test]
    public function validatePackageWithUnknownIdentifierReturnsError(): void
    {
        // Arrange

        $qtiPackage = new QtiPackageMock(
            new ResourceCollection(),
            ManifestMock::create()
        );

        $xml = new DOMDocument();
        $xml->loadXML(file_get_contents(__DIR__ . '/resources/item001.xml'));

        $qtiPackage->addResource(new Resource(
            'item001',
            ResourceType::ASSESSMENT_ITEM,
            'item001.xml',
            new ResourceFileCollection([
                new ResourceFile('item001.xml', new XmlFileContent($xml)),
            ]),
            new ManifestResourceDependencyCollection(),
        ));

        // Act

        $errors = $this->validator->validate($qtiPackage);

        // Assert

        $this->assertCount(4, $errors);
    }
}
