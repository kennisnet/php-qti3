<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Metadata;

use App\Edurep\Domain\Lom\LearningObjectMetadata;
use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;
use DOMDocument;

readonly class Metadata
{
    public function __construct(public DOMDocument $lomDocument) {}

    public static function fromString(string $content, IXmlReader $xmlReader): self
    {
        return new self($xmlReader->read($content));
    }

    public function getLearningObjectMetadata(): LearningObjectMetadata
    {
        return new LearningObjectMetadata($this->lomDocument);
    }
}
