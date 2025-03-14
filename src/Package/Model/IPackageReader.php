<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model;

use DateTimeImmutable;

interface IPackageReader
{
    public function readFile(string $filepath): string;

    public function getLastModified(): ?DateTimeImmutable;

}
