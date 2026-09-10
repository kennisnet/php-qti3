<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\RubricBlock;

use InvalidArgumentException;
use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Model\QtiElement;

class RubricBlock extends QtiElement
{
    /**
     * `use` is optional in the QTI 3.0 ASI schema, `view` is a required list of
     * views — hence the nullable `$use` and the {@see ViewCollection}.
     */
    public function __construct(
        public readonly ?qtiUse $use,
        public readonly ViewCollection $views,
        public readonly ContentBody $contentBody,
        public readonly ?string $class = null,
    ) {
        if ($this->views->isEmpty()) {
            throw new InvalidArgumentException('A qti-rubric-block must name at least one view.');
        }
    }

    /**
     * Whether this rubric block is addressed to `$view`, e.g. to select the
     * blocks a candidate gets to see.
     */
    public function hasView(View $view): bool
    {
        return $this->views->has($view);
    }

    public function attributes(): array
    {
        return [
            'use' => $this->use?->value,
            'view' => implode(' ', array_map(static fn(View $view): string => $view->value, $this->views->all())),
            'class' => $this->class,
        ];
    }

    public function children(): array
    {
        return [$this->contentBody];
    }
}
