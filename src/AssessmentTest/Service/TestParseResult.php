<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service;

use Qti3\AssessmentTest\Model\AssessmentTest;
use Qti3\Shared\Collection\StringCollection;

/**
 * The typed test plus the warnings gathered while parsing it. A non-empty
 * `warnings` collection means the source contained constructs the model cannot
 * hold, so they are lost when the test is serialized again.
 */
final readonly class TestParseResult
{
    public function __construct(
        public AssessmentTest $test,
        public StringCollection $warnings,
    ) {}
}
