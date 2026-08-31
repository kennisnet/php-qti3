<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\ResponseProcessing;

use Qti3\Shared\Model\Processing\IProcessingElement;
use Qti3\Shared\Model\QtiElement;
use Qti3\AssessmentItem\Model\State\ItemState;
use Qti3\Shared\Collection\StringCollection;

class ResponseProcessing extends QtiElement
{
    public const string TEMPLATE_MATCH_CORRECT = 'https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/match_correct.xml';
    public const string TEMPLATE_MAP_RESPONSE = 'https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/map_response.xml';
    public const string TEMPLATE_MAP_RESPONSE_POINT = 'https://purl.imsglobal.org/spec/qti/v3p0/rptemplates/map_response_point.xml';

    /**
     * @param array<int,IProcessingElement> $elements
     * @param string|null $template standard template URL this processing is equivalent to;
     *                              re-emitted on serialization so the reference is not lost
     */
    public function __construct(
        public readonly array $elements,
        public readonly ?string $template = null,
    ) {}

    /**
     * Template for response processing similar to {@see self::TEMPLATE_MATCH_CORRECT}.
     * Custom scores deviate from the standard template, so only the default
     * scores carry the template reference.
     */
    public static function matchCorrect(
        float $scoreCorrect = 1.0,
        float $scoreIncorrect = 0.0,
    ): self {
        $isStandardTemplate = $scoreCorrect === 1.0 && $scoreIncorrect === 0.0;

        return new self(
            [ResponseCondition::matchCorrect($scoreCorrect, $scoreIncorrect)],
            $isStandardTemplate ? self::TEMPLATE_MATCH_CORRECT : null,
        );
    }

    /**
     * Template for response processing equal to {@see self::TEMPLATE_MAP_RESPONSE}.
     */
    public static function mapResponse(): self
    {
        return new self(
            [ResponseCondition::mapResponse()],
            self::TEMPLATE_MAP_RESPONSE,
        );
    }

    /**
     * Template for response processing equal to {@see self::TEMPLATE_MAP_RESPONSE_POINT}.
     */
    public static function mapResponsePoint(): self
    {
        return new self(
            [ResponseCondition::mapResponsePoint()],
            self::TEMPLATE_MAP_RESPONSE_POINT,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function attributes(): array
    {
        return ['template' => $this->template];
    }

    public function children(): array
    {
        return $this->elements;
    }

    public function processResponses(ItemState $state): void
    {
        foreach ($this->elements as $element) {
            $element->processResponses($state);
        }
    }

    public function validate(ItemState $itemState): StringCollection
    {
        $errors = new StringCollection();

        foreach ($this->elements as $element) {
            $errors = $errors->mergeWith($element->validate($itemState));
        }

        return $errors;
    }
}
