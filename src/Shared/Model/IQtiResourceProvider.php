<?php

declare(strict_types=1);

namespace Qti3\Shared\Model;

interface IQtiResourceProvider
{
    public function getSource(): ?string;

    /**
     * Whether the source is a library-provided path that is safe to read from
     * the local filesystem. Sources originating from item content are not
     * trusted: a local path there ("/etc/passwd", "../secret") must never be
     * read, only in-package files, data URIs and http(s) URLs.
     */
    public function isTrustedSource(): bool;

    public function isBinary(): bool;

    public function getResource(): ?QtiResource;

    public function setResource(QtiResource $resource): void;
}
