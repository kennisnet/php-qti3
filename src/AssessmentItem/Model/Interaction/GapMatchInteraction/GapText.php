<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\GapMatchInteraction;

use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

final class GapText extends QtiElement
{
    public function __construct(
        readonly public string $identifier,
        readonly public int $matchMax,
        readonly public ContentNodeCollection $content,
        readonly public int $matchMin = 0,
        readonly public ?string $matchGroup = null,
        //	Identifier of a template variable used to control the visibility of the qti-gap-text
        readonly public ?string $templateIdentifier = null,
        readonly public string $showHide = 'show',
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
            'match-max' => (string) $this->matchMax,
            'match-min' => (string) $this->matchMin,
            'match-group' => $this->matchGroup,
            'template-identifier' => $this->templateIdentifier,
            'show-hide' => $this->showHide,
        ];
    }

    public function children(): array
    {
        return $this->content->all();
    }

}
