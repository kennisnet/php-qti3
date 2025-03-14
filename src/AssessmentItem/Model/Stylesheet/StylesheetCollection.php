<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Stylesheet;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<Stylesheet>
 */
class StylesheetCollection extends AbstractCollection
{
    public function getType(): string
    {
        return Stylesheet::class;
    }
}
