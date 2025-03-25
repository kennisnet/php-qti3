<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model\Manifest;

use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestFactory;
use App\SharedKernel\Infrastructure\Serializer\XmlReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ManifestFactoryTest extends TestCase
{
    #[Test]
    public function createReturnsManifest(): void
    {
        $manifestFactory = new ManifestFactory(
            new XmlReader()
        );

        $manifest = $manifestFactory->createFromXmlString('<manifest />');

        $this->assertInstanceOf(Manifest::class, $manifest);
    }
}
