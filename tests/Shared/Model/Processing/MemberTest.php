<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model\Processing;

use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Correct;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Member;
use App\SharedKernel\Domain\Qti\Shared\Model\Processing\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MemberTest extends TestCase
{
    private Member $member;

    protected function setUp(): void
    {
        $variable = new Variable('variable');
        $set = new Correct('set');

        $this->member = new Member($variable, $set);
    }

    #[Test]
    public function testMember(): void
    {
        $this->assertEquals(
            [
                new Variable('variable'),
                new Correct('set'),
            ],
            $this->member->children()
        );
    }
}
