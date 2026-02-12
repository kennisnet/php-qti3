<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Repository;

use Qti3\Shared\Exception\ResourceNotFoundException;
use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentItem\Model\AssessmentItemId;

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
