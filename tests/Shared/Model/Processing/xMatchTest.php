<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Correct;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\xMatch;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class xMatchTest extends TestCase
{
    private xMatch $xMatch;
    private Variable $variable;
    private Correct $correct;

    protected function setUp(): void
    {
        $this->variable = new Variable('variable');
        $this->correct = new Correct('identifier');
        $this->xMatch = new xMatch($this->variable, $this->correct);
    }

    #[Test]
    public function testxMatch(): void
    {
        $this->assertEquals('qti-match', $this->xMatch->tagName());
        $this->assertInstanceOf(Variable::class, $this->xMatch->children()[0]);
        $this->assertInstanceOf(Correct::class, $this->xMatch->children()[1]);
    }
}
