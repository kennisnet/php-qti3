<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\MatchInteraction;

use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;

class SimpleAssociableChoice extends QtiElement
{
    public function __construct(
        public string $identifier,
        public ContentNodeCollection $content,
        public int $matchMax = 1,
    ) {}

    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
            'match-max' => (string) $this->matchMax,
        ];
    }

    public function children(): array
    {
        return $this->content->all();
    }
}
