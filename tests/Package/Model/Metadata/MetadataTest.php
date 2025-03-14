<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Metadata;

use App\Edurep\Domain\Lom\LearningObjectMetadata;
use App\SharedKernel\Domain\Qti\Package\Model\Metadata\Metadata;
use DOMDocument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MetadataTest extends TestCase
{
    #[Test]
    public function itCanRetrieveLomDocument(): void
    {
        $domDocument = new DOMDocument();
        $xml = <<<XML
        <lom xmlns="http://ltsc.ieee.org/xsd/LOM">
        </lom>
        XML;
        $domDocument->loadXML($xml);
        $metadata = new Metadata($domDocument);

        $this->assertInstanceOf(LearningObjectMetadata::class, $metadata->getLearningObjectMetadata());
    }
}
