<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Package\Exception;

use App\SharedKernel\Domain\Qti\Package\Exception\InvalidQtiPackageException;
use App\SharedKernel\Domain\StringCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvalidQtiPackageExceptionTest extends TestCase
{
    #[Test]
    public function itContainsValidationErrorsAndStandardMessage(): void
    {
        $errors = new StringCollection(['Missing manifest file', 'Invalid XML format']);
        $exception = new InvalidQtiPackageException($errors);

        $this->assertSame('invalid_qti_package', $exception->errorCode());
        $this->assertSame('QTI package is invalid', $exception->getMessage());
        $this->assertSame($errors, $exception->validationErrors);
    }

    #[Test]
    public function itCanContainAnEmptyErrorList(): void
    {
        $errors = new StringCollection();
        $exception = new InvalidQtiPackageException($errors);

        $this->assertSame('invalid_qti_package', $exception->errorCode());
        $this->assertSame('QTI package is invalid', $exception->getMessage());
        $this->assertCount(0, $exception->validationErrors);
    }
}
