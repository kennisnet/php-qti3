<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\PackageFile;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\IFileContent;
use InvalidArgumentException;

class XsdFile extends PackageFile
{
    public function __construct(
        string $name,
        IFileContent $content
    ) {
        if (!str_ends_with($name, '.xsd')) {
            throw new InvalidArgumentException('XSD file name must end with .xsd');
        }
        parent::__construct($name, $content);
    }
}
