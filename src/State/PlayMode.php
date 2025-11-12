<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\State;

use InvalidArgumentException;

enum PlayMode: string
{
    case PREVIEW = 'preview'; // For previewing
    case FORMATIVE = 'formative'; // For practicing and learning
    case SUMMATIVE = 'summative'; // For assessing

    public static function fromString(string $mode): self
    {
        return match (strtolower($mode)) {
            'preview' => self::PREVIEW,
            'formative' => self::FORMATIVE,
            'summative' => self::SUMMATIVE,
            default => throw new InvalidArgumentException("Invalid play mode: $mode"),
        };
    }
}
