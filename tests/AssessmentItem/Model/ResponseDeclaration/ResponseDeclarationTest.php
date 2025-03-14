<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseDeclarationTest extends TestCase
{
    private ResponseDeclaration $responseDeclaration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->responseDeclaration = new ResponseDeclaration(
            baseType: BaseType::STRING,
            cardinality: Cardinality::SINGLE,
            identifier: 'responseDeclaration'
        );
    }

    #[Test]
    public function testResponseDeclaration(): void
    {
        $expectedAttributes = [
            'identifier' => 'responseDeclaration',
            'cardinality' => 'single',
            'base-type' => 'string',
        ];

        $this->assertEquals($expectedAttributes, $this->responseDeclaration->attributes());
        $this->assertEquals([null, null, null], $this->responseDeclaration->children());
    }
}
