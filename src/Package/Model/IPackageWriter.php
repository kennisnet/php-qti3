<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model;

interface IPackageWriter
{
    public function write(QtiPackage $qtiPackage): void;

    public function getPublicUrl(): string;
}
