<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\IProcessingElement;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class ResponseProcessing extends QtiElement
{
    /**
     * @param array<int,IProcessingElement> $elements
     */
    public function __construct(
        public readonly array $elements
    ) {}

    /**
     * Template for response processing similar to https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/match_correct.xml
     */
    public static function matchCorrect(
        float $scoreCorrect = 1.0,
        float $scoreIncorrect = 0.0,
    ): self {
        return new self([ResponseCondition::matchCorrect($scoreCorrect, $scoreIncorrect)]);
    }

    /**
     * Template for response processing equal to https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/map_response.xml
     */
    public static function mapResponse(): self
    {
        return new self(
            [ResponseCondition::mapResponse()]
        );
    }

    public function children(): array
    {
        return $this->elements;
    }
}
