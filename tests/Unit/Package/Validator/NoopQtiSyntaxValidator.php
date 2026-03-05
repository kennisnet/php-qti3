<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Validator;

use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Validator\IQtiSyntaxValidator;
use Qti3\Shared\Collection\StringCollection;

class NoopQtiSyntaxValidator implements IQtiSyntaxValidator
{
    public function validateZipPackage(string $qtiPackageFilename): StringCollection
    {
        return new StringCollection();
    }

    public function validate(QtiPackage $qtiPackage): StringCollection
    {
        return new StringCollection();
    }
}
