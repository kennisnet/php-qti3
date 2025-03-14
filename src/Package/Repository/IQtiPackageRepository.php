<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Repository;

use App\SharedKernel\Domain\Exception\ResourceNotFoundException;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackage;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackageCollection;
use App\SharedKernel\Domain\Qti\Package\Model\QtiPackageId;

interface IQtiPackageRepository
{
    public function findAll(): QtiPackageCollection;

    /** @throws ResourceNotFoundException */
    public function findById(QtiPackageId $id): QtiPackage;

    public function save(QtiPackage $qtiPackage): void;

    public function delete(QtiPackageId $qtiPackageId): void;
}
