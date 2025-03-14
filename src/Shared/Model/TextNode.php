<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

use Stringable;

class TextNode implements IContentNode, Stringable
{
    public function __construct(public string $content) {}

    public function getContentForXml(): string
    {
        return $this->content;
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
