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

    public const string TEMPLATE_NAME_MATCH_CORRECT = 'match_correct';
    public const string TEMPLATE_NAME_MAP_RESPONSE = 'map_response';
    public const string TEMPLATE_NAME_MAP_RESPONSE_POINT = 'map_response_point';

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
        ?string $template = null,
    ): self {
        $isStandardTemplate = $scoreCorrect === 1.0 && $scoreIncorrect === 0.0;

        return new self(
            [ResponseCondition::matchCorrect($scoreCorrect, $scoreIncorrect)],
            $isStandardTemplate ? $template ?? self::TEMPLATE_MATCH_CORRECT : null,
        );
    }

    /**
     * Template for response processing equal to {@see self::TEMPLATE_MAP_RESPONSE}.
     */
    public static function mapResponse(?string $template = null): self
    {
        return new self(
            [ResponseCondition::mapResponse()],
            $template ?? self::TEMPLATE_MAP_RESPONSE,
        );
    }

    /**
     * Template for response processing equal to {@see self::TEMPLATE_MAP_RESPONSE_POINT}.
     */
    public static function mapResponsePoint(?string $template = null): self
    {
        return new self(
            [ResponseCondition::mapResponsePoint()],
            $template ?? self::TEMPLATE_MAP_RESPONSE_POINT,
        );
    }

    /**
     * Resolves a response processing template URL to the name of the standard
     * template it refers to, or null when the URL is not a known template.
     *
     * The QTI specification publishes the templates under several base URLs
     * (`purl.imsglobal.org/spec/qti/v3p0/rptemplates/`,
     * `www.imsglobal.org/question/qti_v2p1/rptemplates/`, ...) and the `.xml`
     * extension is optional, so only the template file name is significant.
     */
    public static function templateName(string $template): ?string
    {
        if (preg_match('~/rptemplates/([^/?\#]+)$~i', trim($template), $matches) !== 1) {
            return null;
        }

        $name = strtolower(preg_replace('/\\.xml$/i', '', $matches[1]) ?? '');

        return match ($name) {
            self::TEMPLATE_NAME_MATCH_CORRECT,
            self::TEMPLATE_NAME_MAP_RESPONSE,
            self::TEMPLATE_NAME_MAP_RESPONSE_POINT => $name,
            default => null,
        };
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
