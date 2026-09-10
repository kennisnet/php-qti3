<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\RubricBlock;

use Qti3\Shared\Collection\AbstractCollection;

/**
 * The views of a `qti-rubric-block`: `view` is an `xs:list` in the schema, so a
 * block can name several. Order is preserved.
 *
 * @template-extends AbstractCollection<View>
 */
class ViewCollection extends AbstractCollection
{
    public function getType(): string
    {
        return View::class;
    }
}
