<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Repository;

use App\SharedKernel\Domain\Exception\ResourceNotFoundException;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTest;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\AssessmentTestId;

interface IAssessmentTestRepository
{
    /** @throws ResourceNotFoundException */
    public function getById(AssessmentTestId $assessmentTestId): AssessmentTest;
}
