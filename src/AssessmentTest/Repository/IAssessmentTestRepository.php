<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Repository;

use Qti3\Shared\Exception\ResourceNotFoundException;
use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\AssessmentTest\Model\AssessmentTestId;

interface IAssessmentTestRepository
{
    /** @throws ResourceNotFoundException */
    public function getById(AssessmentTestId $assessmentTestId): AssessmentTest;
}
