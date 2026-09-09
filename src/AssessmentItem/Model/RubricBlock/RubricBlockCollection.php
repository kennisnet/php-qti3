<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\RubricBlock;

use Qti3\Shared\Collection\AbstractCollection;

/**
 * @template-extends AbstractCollection<RubricBlock>
 */
class RubricBlockCollection extends AbstractCollection
{
    public function getType(): string
    {
        return RubricBlock::class;
    }
}
