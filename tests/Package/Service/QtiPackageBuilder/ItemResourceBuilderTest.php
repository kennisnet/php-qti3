<?php

declare(strict_types=1);

namespace Qti3\Tests\Package\Service\QtiPackageBuilder;

use Qti3\Package\Model\Manifest\ManifestResourceDependencyCollection;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Service\QtiPackageBuilder\ItemResourceBuilder;
use Qti3\Infrastructure\Serializer\XmlBuilder;
use Qti3\Infrastructure\Serializer\XmlReader;
use Qti3\Tests\AssessmentItem\Model\AssessmentItemStub;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ItemResourceBuilderTest extends TestCase
{
    private ItemResourceBuilder $assessmentItemBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessmentItemBuilder = new ItemResourceBuilder(new XmlBuilder(), new XmlReader());
    }

    #[Test]
    public function testBuild(): void
    {
        $assessmentItemResource = $this->assessmentItemBuilder->build(
            'ITEM001',
            AssessmentItemStub::assessmentItem(),
            new ManifestResourceDependencyCollection(),
        );

        $this->assertInstanceOf(Resource::class, $assessmentItemResource);
        $this->assertStringContainsString(
            '<qti-assessment-item',
            (string) $assessmentItemResource->files->first()->getContent(),
        );
        $this->assertEquals('ITEM001.xml', $assessmentItemResource->href);
    }
}
