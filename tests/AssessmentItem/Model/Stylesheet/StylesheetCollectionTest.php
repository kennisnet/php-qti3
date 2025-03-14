<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Stylesheet;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Stylesheet\Stylesheet;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Stylesheet\StylesheetCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StylesheetCollectionTest extends TestCase
{
    private StylesheetCollection $stylesheetCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stylesheetCollection = new StylesheetCollection();
    }

    #[Test]
    public function testGetType(): void
    {
        $this->assertEquals(Stylesheet::class, $this->stylesheetCollection->getType());
    }
}
