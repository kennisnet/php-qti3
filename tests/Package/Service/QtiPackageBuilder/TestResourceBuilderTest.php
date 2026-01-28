<?php

declare(strict_types=1);

namespace Qti3\Tests\Package\Service\QtiPackageBuilder;

use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Service\QtiPackageBuilder\TestResourceBuilder;
use Qti3\Infrastructure\Serializer\XmlBuilder;
use Qti3\Infrastructure\Serializer\XmlReader;
use Qti3\Tests\AssessmentTest\Model\AssessmentTestStub;
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
            new ManifestResourceDependencyCollection(),
        );

        $this->assertInstanceOf(Resource::class, $assessmentTestResource);
        $this->assertStringContainsString(
            '<qti-assessment-test',
            (string) $assessmentTestResource->files->first()->getContent(),
        );
    }
}
