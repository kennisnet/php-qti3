<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Model;

use App\SharedKernel\Domain\Qti\Package\Model\DeletedQtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DeletedQtiPackageTest extends TestCase
{
    #[Test]
    public function itCreatesAnEmptyQtiPackage(): void
    {
        $package = new DeletedQtiPackage();

        $this->assertInstanceOf(QtiPackage::class, $package);

        $resources = $package->resources;
        $this->assertInstanceOf(ResourceCollection::class, $resources);
        $this->assertCount(0, $resources);

        $manifest = $package->manifest;
        $this->assertInstanceOf(Manifest::class, $manifest);
    }
}
