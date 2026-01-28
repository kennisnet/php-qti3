<?php

declare(strict_types=1);

namespace Qti3\Tests\Package\Validator;

use Qti3\Exception\ErrorType;
use Qti3\Package\Validator\QtiPackageValidationError;
use Qti3\StringCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QtiPackageValidationErrorTest extends TestCase
{
    #[Test]
    public function validationErrorHasCorrectCodeAndType(): void
    {
        // Arrange & Act

        $validationError = new QtiPackageValidationError(new StringCollection(['error']));

        // Assert

        $this->assertEquals('validation_errors', $validationError->errorCode());
        $this->assertEquals(ErrorType::VALIDATION, $validationError->errorType());
    }
}
