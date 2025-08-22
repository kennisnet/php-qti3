<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\FileContent;

interface IFileContent
{
    public function getContent(): string;

    /**
     * @return iterable<string>
     */
    public function getStream(): iterable;
}
