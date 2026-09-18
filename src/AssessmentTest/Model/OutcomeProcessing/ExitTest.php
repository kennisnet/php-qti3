<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Model\OutcomeProcessing;

use Qti3\Shared\Model\QtiElement;

/** `<qti-exit-test/>`: stops outcome processing at this point. */
class ExitTest extends QtiElement implements IOutcomeProcessingElement {}
