<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction\MatchInteraction;

use Qti3\Shared\Model\AbstractBaseSequenceElement;
use Qti3\Shared\Model\BaseSequenceAttributes;
use Qti3\AssessmentItem\Model\Interaction\Prompt;

/**
 * The match interaction presents two sets of choices and requires the candidate
 * to create associations between them.
 */
class MatchInteraction extends AbstractBaseSequenceElement
{
    public function __construct(
        public SimpleMatchSet $simpleMatchSet1,
        public SimpleMatchSet $simpleMatchSet2,
        public ?Prompt $prompt = null,
        public string $responseIdentifier = 'RESPONSE',
        public bool $shuffle = false,
        public ?int $maxAssociations = null,
        public ?int $minAssociations = null,
        BaseSequenceAttributes $attributes = new BaseSequenceAttributes(),
    ) {
        parent::__construct($attributes);
    }

    public function attributes(): array
    {
        return [
            'response-identifier' => $this->responseIdentifier,
            'shuffle' => $this->shuffle ? 'true' : 'false',
            'max-associations' => $this->maxAssociations === null ? null : (string) $this->maxAssociations,
            'min-associations' => $this->minAssociations === null ? null : (string) $this->minAssociations,
            ...$this->baseSequenceAttributes(),
        ];
    }

    public function children(): array
    {
        return [
            $this->prompt,
            $this->simpleMatchSet1,
            $this->simpleMatchSet2,
        ];
    }
}
