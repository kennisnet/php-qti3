<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration;

use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OutcomeDeclarationCollectionTest extends TestCase
{
    private OutcomeDeclarationCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new OutcomeDeclarationCollection();
    }

    #[Test]
    public function itShouldReturnOutcomeDeclarationClassAsType(): void
    {
        $this->assertEquals(
            OutcomeDeclaration::class,
            $this->collection->getType()
        );
    }

    #[Test]
    public function requestingANonExistingIdentifierThrowsException(): void
    {
        // Act & Assert

        $this->expectException(InvalidArgumentException::class);
        $this->collection->getByIdentifier('non-existing');
    }
}
