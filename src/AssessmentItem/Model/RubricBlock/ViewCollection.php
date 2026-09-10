<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\RubricBlock;

use Qti3\Shared\Collection\AbstractCollection;

/**
 * The views a `qti-rubric-block` is addressed to. The QTI 3.0 ASI schema types
 * the `view` attribute as an `xs:list` of `ViewEnumDType`, so a rubric block can
 * name more than one view (`view="candidate scorer"`). Order is preserved so the
 * attribute round-trips unchanged.
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
