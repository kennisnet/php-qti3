<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model;

use App\SharedKernel\Domain\Qti\Package\Model\Manifest\Manifest;
use App\SharedKernel\Domain\Qti\Package\Model\Resource\ResourceCollection;
use DOMDocument;

class DeletedQtiPackage extends QtiPackage
{
    public function __construct()
    {
        parent::__construct(new ResourceCollection(), Manifest::fromDomDocument(new DOMDocument()));
    }
}
