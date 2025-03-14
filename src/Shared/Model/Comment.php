<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

class Comment extends TextNode
{
    public function getContentForXml(): string
    {
        return $this->content;
    }
}
