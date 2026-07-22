<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use Qti3\Package\Model\Resource\Resource;
use Qti3\Shared\Collection\StringCollection;

/**
 * The outcome of a {@see PackageEditor} operation: the affected item resource
 * (null for reorder) plus the warnings raised while editing. Warnings surface
 * data loss — e.g. the edited test contained constructs the model cannot hold
 * and that were dropped when its XML was regenerated.
 */
final readonly class EditResult
{
    public function __construct(
        public ?Resource $resource,
        public StringCollection $warnings,
    ) {}
}
