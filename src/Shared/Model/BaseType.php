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

    public function fits(BaseType $type): bool
    {
        if ($this->value === $type->value) {
            return true;
        }
        if ($this->value === BaseType::FLOAT->value) {
            return $type->value === BaseType::INTEGER->value || $type->value === BaseType::FLOAT->value;
        }
        if ($this->value === BaseType::STRING->value) {
            return $type->value === BaseType::STRING->value ||
                $type->value === BaseType::IDENTIFIER->value ||
                $type->value === BaseType::INTEGER->value ||
                $type->value === BaseType::FLOAT->value;
        }

        return false;
    }
}
