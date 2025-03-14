<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Filesystem\Zip\Exception;

use RuntimeException;

class ZipArchiveOpenFileException extends RuntimeException
{
    public function __construct(string $archiveFilePath)
    {
        parent::__construct(sprintf('Unable to create or overwrite ZipArchive for filepath <%s>', $archiveFilePath));
    }
}
