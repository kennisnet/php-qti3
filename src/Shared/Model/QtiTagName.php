<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

use ReflectionClass;
use Stringable;

readonly class QtiTagName implements Stringable
{
    public function __construct(private object $object) {}

    public function __toString(): string
    {
        return  'qti-' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', new ReflectionClass($this->object)->getShortName()) ?? '');
    }
}
