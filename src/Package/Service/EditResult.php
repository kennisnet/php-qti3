<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use Qti3\Package\Model\Resource\Resource;
use Qti3\Shared\Collection\StringCollection;

/**
 * The outcome of a {@see PackageEditor} operation: the affected item resource (null for
 * reorder and rubric blocks) plus warnings, which surface constructs dropped on rewrite.
 */
final readonly class EditResult
{
    public function __construct(
        public ?Resource $resource,
        public StringCollection $warnings,
    ) {}
}
