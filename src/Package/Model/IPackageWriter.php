<?php

declare(strict_types=1);

namespace Qti3\Package\Model;

interface IPackageWriter
{
    /**
     * Write the package to this writer's destination.
     *
     * When `$skipUnmodifiedFiles` is true, files that were not generated or
     * changed in memory ({@see \Qti3\Package\Model\PackageFile\IPackageFile::isModified()})
     * may be left untouched at the destination. This is ONLY safe when writing
     * back over the package's original location: the unchanged files are
     * expected to already be present there. Writers that build a fresh artifact
     * (e.g. a zip archive) always write every file regardless of this flag.
     */
    public function write(QtiPackage $qtiPackage, bool $skipUnmodifiedFiles = false): void;

    public function getPublicUrl(): string;
}
