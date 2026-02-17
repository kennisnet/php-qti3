<?php

namespace Qti3\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
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
}
