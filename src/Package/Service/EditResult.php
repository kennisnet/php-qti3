<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use Qti3\Package\Model\Resource\Resource;
use Qti3\Shared\Collection\StringCollection;

/**
 * The outcome of a {@see PackageEditor} operation; the resource is null for an edit that
 * rewrites no item, such as a reorder or a rubric-block replacement.
 */
final readonly class EditResult
{
    public function __construct(
        public ?Resource $resource,
        public StringCollection $warnings,
    ) {}
}
