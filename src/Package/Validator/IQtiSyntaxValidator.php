<?php

declare(strict_types=1);

namespace Qti3\Package\Validator;

use Qti3\Package\Model\QtiPackage;
use Qti3\Shared\Collection\StringCollection;

/**
 * Performs syntactic validation of a QTI package: verifies that the package
 * structure and its XML content conform to the QTI 3.0 specification.
 *
 * Two implementations are provided out of the box:
 *  - {@see QtiSchemaValidator} — XSD-based, zero extra dependencies, used by default.
 *  - {@see ImsGlobalQtiSyntaxValidator} — wraps the official IMS Global validator
 *    Docker image; requires a running instance and a PSR-18 HTTP client.
 *
 * Pass a custom implementation to {@see \Qti3\QtiClient::__construct()} to override
 * the default XSD validator.
 */
interface IQtiSyntaxValidator extends IQtiPackageValidator
{
    public function validateZipPackage(string $qtiPackageFilename): StringCollection;

    public function validate(QtiPackage $qtiPackage): StringCollection;
}
