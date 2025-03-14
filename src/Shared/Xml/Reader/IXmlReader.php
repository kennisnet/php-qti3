<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Shared\Xml\Reader;

use DOMDocument;

interface IXmlReader
{
    public function read(string $content): DOMDocument;
}
