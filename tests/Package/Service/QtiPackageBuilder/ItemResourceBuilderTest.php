<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder;

use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\Resource;
use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\ItemResourceBuilder;
use App\SharedKernel\Infrastructure\Serializer\XmlBuilder;
use App\SharedKernel\Infrastructure\Serializer\XmlReader;
use App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItemStub;
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
