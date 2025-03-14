<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

final class ContentBody extends QtiElement
{
    public function __construct(
        public ContentNodeCollection $content
    ) {}

    /**
     * @return array<int,IContentNode>
     */
    public function children(): array
    {
        return $this->content->all();
    }
}
