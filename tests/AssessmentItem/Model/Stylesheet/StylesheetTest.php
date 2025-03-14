<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\Stylesheet;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Stylesheet\Stylesheet;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiResource;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StylesheetTest extends TestCase
{
    private Stylesheet $stylesheet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stylesheet = new Stylesheet('path/to/stylesheet.css');
    }

    #[Test]
    public function testAttributes(): void
    {
        $this->assertEquals([
            'href' => '',
            'type' => 'text/css',
        ], $this->stylesheet->attributes());
    }

    #[Test]
    public function testResource(): void
    {
        $this->stylesheet->setResource(new QtiResource('webcontent', 'path/to/stylesheet.css', '', 'stylesheet.css'));
        $this->assertEquals('stylesheet.css', $this->stylesheet->getResource()->filename);
    }
}
