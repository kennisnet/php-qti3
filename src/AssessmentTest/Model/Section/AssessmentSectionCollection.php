<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model\Section;

use App\SharedKernel\Domain\AbstractCollection;

/**
 * @template-extends AbstractCollection<AssessmentSection>
 */
class AssessmentSectionCollection extends AbstractCollection
{
    public function getType(): string
    {
        return AssessmentSection::class;
    }
}
