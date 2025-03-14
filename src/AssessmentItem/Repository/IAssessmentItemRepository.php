<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Repository;

use App\SharedKernel\Domain\Exception\ResourceNotFoundException;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItem;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItemId;

interface IAssessmentItemRepository
{
    /** @throws ResourceNotFoundException */
    public function getById(AssessmentItemId $assessmentItemId): AssessmentItem;

    /**
     * @param array<int,AssessmentItemId> $assessmentItemIds
     * @return array<int,AssessmentItem>
     * @throws ResourceNotFoundException
     */
    public function getByIds(array $assessmentItemIds): array;
}
