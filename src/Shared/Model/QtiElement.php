<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

abstract class QtiElement implements IXmlElement
{
    public static function qtiTagName(): string
    {
        $tagName = new QtiTagName(static::class);

        return (string) $tagName;
    }

    public function tagName(): string
    {
        return static::qtiTagName();
    }

    /**
     * @return array<string, string|null>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * @return array<int,IContentNode|null>
     */
    public function children(): array
    {
        return [];
    }
}
