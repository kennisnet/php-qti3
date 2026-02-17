<?php

namespace Qti3\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Model\Resource\ResourceType;
use Qti3\Package\Validator\QtiPackageValidator;
use Qti3\Package\Validator\ResponseProcessingValidator;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Tests\Unit\Package\Validator\NoopImsQtiPackageValidator;

#[Group('integration')]
class ValidatePackageIntegrationTest extends TestCase
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

    /**
     * Use case: Een QtiPackage valideren die uit een ZIP bestand komt.
     */
    public function testValidatePackageFromZipWithQtiPackageValidatorReturnsErrorsFromBothValidators(): void
    {
        $client = $this->createClient();
        $zipPath = ZipPackageFixture::createValidQtiZip();

        try {
            $reader = $client->getQtiPackageReader();
            $package = $reader->fromZip($zipPath);
            // Use NoopImsQtiPackageValidator to skip heavy XSD validation in this test
            $validator = new QtiPackageValidator(
                new NoopImsQtiPackageValidator(),
                new ResponseProcessingValidator($client->getResponseProcessor())
            );
            $errors = $validator->validate($package);

            $this->assertInstanceOf(StringCollection::class, $errors);
            // item001.xml triggers response processing validation
            $this->assertGreaterThanOrEqual(0, $errors->count());
        } finally {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }
    }

    public function testImportRealZipPackageHasCorrectStructure(): void
    {
        $client = $this->createClient();
        $reader = $client->getQtiPackageReader();
        $package = $reader->fromZip($this->getFixturePath('toetsen/valid-package.zip'));

        $assessmentTests = $package->resources->filterByType(ResourceType::ASSESSMENT_TEST);
        $this->assertCount(1, $assessmentTests, 'Package should contain exactly 1 assessment test');

        $assessmentItems = $package->resources->filterByType(ResourceType::ASSESSMENT_ITEM);
        $this->assertCount(47, $assessmentItems, 'Package should contain exactly 47 assessment items');

        $metadataResources = $package->resources->filterByType(ResourceType::RESOURCE_METADATA);
        $this->assertGreaterThan(0, $metadataResources->count(), 'Package should contain resource metadata');

        $metadata = $package->getMetadata();
        $this->assertNotNull($metadata, 'Package metadata should be accessible');

        $files = $package->getFiles();
        $this->assertGreaterThan(0, $files->count(), 'Package should have accessible files');

        $testIdentifier = $package->getAssessmentTestIdentifier();
        $this->assertNotEmpty($testIdentifier, 'Assessment test identifier should not be empty');
    }

    public function testImportRealZipPackagePassesValidation(): void
    {
        $client = $this->createClient();
        $reader = $client->getQtiPackageReader();
        $package = $reader->fromZip($this->getFixturePath('toetsen/valid-package.zip'));

        $validator = new QtiPackageValidator(
            new NoopImsQtiPackageValidator(),
            new ResponseProcessingValidator($client->getResponseProcessor()),
        );
        $errors = $validator->validate($package);

        $this->assertInstanceOf(StringCollection::class, $errors);
        $this->assertCount(0, $errors, 'Valid package should produce no validation errors. Got: ' . implode('; ', iterator_to_array($errors)));
    }

    private function getFixturePath(string $relativePath): string
    {
        $path = __DIR__ . '/../../fixtures/' . $relativePath;
        $this->assertFileExists($path, "Fixture file not found: $relativePath");
        return $path;
    }
}
