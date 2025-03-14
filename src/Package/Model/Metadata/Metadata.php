<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Metadata;

use App\Edurep\Domain\Lom\LearningObjectMetadata;
use DOMDocument;

readonly class Metadata
{
    public function __construct(public DOMDocument $lomDocument) {}

    public function getLearningObjectMetadata(): LearningObjectMetadata
    {
        return new LearningObjectMetadata($this->lomDocument);
    }
}
