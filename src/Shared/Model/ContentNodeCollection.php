<?php

declare(strict_types=1);

namespace Qti3\Shared\Model;

use Qti3\AbstractCollection;

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
