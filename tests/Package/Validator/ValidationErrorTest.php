<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Validator;

use App\SharedKernel\Domain\Qti\Package\Validator\ValidationError;
use App\SharedKernel\Domain\StringCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ValidationErrorTest extends TestCase
{
    #[Test]
    public function validationErrorHasCorrectCode(): void
    {
        // Arrange & Act

        $validationError = new ValidationError(new StringCollection(['error']));

        // Assert

        $this->assertEquals('validation_errors', $validationError->errorCode());
    }
}
