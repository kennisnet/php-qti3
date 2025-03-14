<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

enum Cardinality: string
{
    case SINGLE = 'single';
    case MULTIPLE = 'multiple';
    case ORDERED = 'ordered';
    case RECORD = 'record';
}
