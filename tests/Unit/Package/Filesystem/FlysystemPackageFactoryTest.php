<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Filesystem;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Filesystem\FlysystemPackageFactory;
use Qti3\Package\Model\IPackageReader;
use Qti3\Package\Model\IPackageWriter;

final class FlysystemPackageFactoryTest extends TestCase
{
    private FlysystemPackageFactory $factory;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->factory = new FlysystemPackageFactory($this->filesystem);
    }

    #[Test]
    public function itVendsAWriter(): void
    {
        $this->assertInstanceOf(IPackageWriter::class, $this->factory->getWriter('folder'));
    }

    #[Test]
    public function itVendsAReaderForAnExistingFolder(): void
    {
        $this->filesystem->write('folder/imsmanifest.xml', '<manifest/>');

        $this->assertInstanceOf(IPackageReader::class, $this->factory->getReader('folder'));
    }
}
