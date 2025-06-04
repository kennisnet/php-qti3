<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Resource;

use App\SharedKernel\Domain\Qti\Package\Model\Manifest\ManifestResourceDependencyCollection;
use App\SharedKernel\Domain\Qti\Package\Model\Metadata\Metadata;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceFileCollection;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use InvalidArgumentException;

class Resource
{
    public function __construct(
        public readonly string $identifier,
        public readonly ResourceType $type,
        public readonly ?string $href,
        public readonly ResourceFileCollection $files,
        public readonly ManifestResourceDependencyCollection $resourceDependencies,
        public ?Metadata $metadata = null,
    ) {
        $this->validateHref();
    }

    public function getMainFile(): ?ResourceFile
    {
        if (!$this->href) {
            return null;
        }

        foreach ($this->files as $file) {
            if ($file->href === $this->href) {
                return $file;
            }
        }

        return null; // @codeCoverageIgnore
    }

    private function validateHref(): void
    {
        if (!$this->href) {
            return;
        }

        /** @var ResourceFile $file */
        foreach ($this->files->all() as $file) {
            if ($file->href === $this->href) {
                return;
            }
        }

        throw new InvalidArgumentException(sprintf('Resource with identifier %s has invalid href %s', $this->identifier, $this->href));
    }
}
