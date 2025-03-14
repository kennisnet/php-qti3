<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

enum BaseType: string
{
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case POINT = 'point';
    case PAIR = 'pair';
    case DIRECTED_PAIR = 'directedPair';
    case DURATION = 'duration';
    case FILE = 'file';
    case IDENTIFIER = 'identifier';
    case URI = 'uri';
}
