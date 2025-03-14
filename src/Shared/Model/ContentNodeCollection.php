<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<IContentNode>
 */
class ContentNodeCollection extends AbstractCollection
{
    public function getType(): string
    {
        return IContentNode::class;
    }
}
