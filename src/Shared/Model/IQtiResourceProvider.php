<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

interface IQtiResourceProvider
{
    public function getSource(): ?string;

    public function isBinary(): bool;

    public function getResource(): ?QtiResource;

    public function setResource(QtiResource $resource): void;
}
