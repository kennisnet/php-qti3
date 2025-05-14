<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Correct;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\qtiMatch;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class qtiMatchTest extends TestCase
{
    private qtiMatch $xMatch;
    private Variable $variable;
    private Correct $correct;

    protected function setUp(): void
    {
        $this->variable = new Variable('variable');
        $this->correct = new Correct('identifier');
        $this->xMatch = new qtiMatch($this->variable, $this->correct);
    }

    #[Test]
    public function testxMatch(): void
    {
        $this->assertEquals('qti-match', $this->xMatch->tagName());
        $this->assertInstanceOf(Variable::class, $this->xMatch->children()[0]);
        $this->assertInstanceOf(Correct::class, $this->xMatch->children()[1]);
    }
}
