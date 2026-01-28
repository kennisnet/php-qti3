<?php

declare(strict_types=1);

namespace Qti3\Package\Service\QtiPackageBuilder;

use Qti3\Shared\Model\QtiResource;

interface IResourceValidator
{
    public function validate(QtiResource $resource): void;
}
