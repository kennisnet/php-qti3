<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Exception\ErrorType;
use App\SharedKernel\Domain\Qti\Package\Validator\QtiPackageValidationError;
use App\SharedKernel\Domain\StringCollection;
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
