<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder;

use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use App\SharedKernel\Infrastructure\Serializer\XmlBuilder;
use App\SharedKernel\Infrastructure\Serializer\XmlReader;
use App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTestStub;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TestResourceBuilderTest extends TestCase
{
    private TestResourceBuilder $assessmentTestBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessmentTestBuilder = new TestResourceBuilder(new XmlBuilder(), new XmlReader());
    }

    #[Test]
    public function testBuild(): void
    {
        $assessmentTestResource = $this->assessmentTestBuilder->build(
            AssessmentTestStub::assessmentTest(),
            new ManifestResourceDependencyCollection()
        );

        $this->assertInstanceOf(Resource::class, $assessmentTestResource);
        $this->assertStringContainsString(
            '<qti-assessment-test',
            (string) $assessmentTestResource->files->first()->getContent()
        );
    }
}
