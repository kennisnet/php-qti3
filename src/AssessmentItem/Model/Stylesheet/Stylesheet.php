<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Stylesheet;

use App\SharedKernel\Domain\Qti\Shared\Model\IQtiResourceProvider;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiResource;

class Stylesheet extends QtiElement implements IQtiResourceProvider
{
    protected ?QtiResource $resource = null;

    public function __construct(
        public readonly string $filePath
    ) {}

    public function getSource(): ?string
    {
        return $this->filePath;
    }

    public function isBinary(): bool
    {
        return false;
    }

    public function getResource(): ?QtiResource
    {
        return $this->resource;
    }

    public function setResource(QtiResource $resource): void
    {
        $this->resource = $resource;
    }

    /**
     * @return array<string, string|null>
     */
    public function attributes(): array
    {
        return [
            'href' => $this->resource ? ($this->resource->relativePath . $this->resource->filename) : '',
            'type' => 'text/css',
        ];
    }
}
