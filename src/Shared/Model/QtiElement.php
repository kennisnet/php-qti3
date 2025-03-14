<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

abstract class QtiElement implements IXmlElement
{
    public function tagName(): string
    {
        $tagName = new QtiTagName($this);

        return (string) $tagName;
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
