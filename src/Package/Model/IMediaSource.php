<?php

declare(strict_types=1);

namespace Qti3\Package\Model;

use Qti3\Package\Model\FileContent\IFileContent;

/**
 * Media files belonging to the package being built or edited, addressed by
 * package-relative path.
 *
 * By default no local file reference in item content is readable: package
 * building can never read an arbitrary file (e.g. "/etc/passwd" or
 * "../secret") into a package. A caller that builds a package from a local
 * source passes the source's media files as an IMediaSource per operation
 * (see {@see \Qti3\Package\Service\QtiPackageBuilder::buildForTest()} and the
 * {@see \Qti3\Package\Service\PackageEditor} item operations), so access stays
 * scoped to the root of that package's source.
 */
interface IMediaSource
{
    public function hasFile(string $filepath): bool;

    public function getFileContent(string $filepath): IFileContent;
}
