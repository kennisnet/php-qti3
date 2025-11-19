<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\AssessmentItem\Service\AssessmentItemDeterminator;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ProcessingElementParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\QtiExpressionParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ResponseDeclarationParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ResponseProcessingParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\ResponseProcessor;
use App\SharedKernel\Domain\Qti\Package\Model\FileContent\MemoryFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\XmlFile;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceType;
use App\SharedKernel\Domain\Qti\Package\Validator\IImsQtiPackageValidator;
use App\SharedKernel\Domain\Qti\Package\Validator\QtiPackageValidator;
use App\SharedKernel\Domain\Qti\Package\Validator\ResponseProcessingValidator;
use App\SharedKernel\Domain\StringCollection;
use App\SharedKernel\Infrastructure\Serializer\XmlReader;
use App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestMock;
use App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\QtiPackageMock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QtiPackageValidatorTest extends TestCase
{
    private QtiPackageValidator $validator;
    private XmlReader $xmlReader;

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
                            new QtiExpressionParser(),
                        ),
                    ),
                    new AssessmentItemDeterminator(),
                ),
            ),
        );
        $this->xmlReader = new XmlReader();
    }

    #[Test]
    public function validatePackageWithUnknownIdentifierReturnsError(): void
    {
        // Arrange

        $qtiPackage = new QtiPackageMock(
            new ResourceCollection(),
            ManifestMock::create(),
        );

        $qtiPackage->addResource(new Resource(
            'item001',
            ResourceType::ASSESSMENT_ITEM,
            'item001.xml',
            new PackageFileCollection([
                new XmlFile(
                    'item001.xml',
                    new MemoryFileContent(file_get_contents(__DIR__ . '/resources/item001.xml')),
                    $this->xmlReader,
                ),
            ]),
            new ManifestResourceDependencyCollection(),
        ));

        // Act

        $errors = $this->validator->validate($qtiPackage);

        // Assert

        $this->assertCount(5, $errors);
    }
}
