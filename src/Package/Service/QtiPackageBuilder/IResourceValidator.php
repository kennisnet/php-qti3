<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder;

use App\SharedKernel\Domain\Qti\Shared\Model\QtiResource;

interface IResourceValidator
{
    public function validate(QtiResource $resource): void;
}
