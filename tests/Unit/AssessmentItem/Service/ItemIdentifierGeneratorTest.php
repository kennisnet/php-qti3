<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Service\ItemIdentifierGenerator;

final class ItemIdentifierGeneratorTest extends TestCase
{
    /**
     * @param list<string> $existingIdentifiers
     */
    #[Test]
    #[DataProvider('identifierProvider')]
    public function itGeneratesTheNextSequentialIdentifier(array $existingIdentifiers, string $expected): void
    {
        $next = (new ItemIdentifierGenerator())->nextIdentifier($existingIdentifiers);

        $this->assertSame($expected, $next);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function identifierProvider(): iterable
    {
        yield 'empty package starts at ITEM001' => [[], 'ITEM001'];
        yield 'sequential identifiers' => [['ITEM001', 'ITEM002', 'ITEM003'], 'ITEM004'];
        yield 'gap-safe: uses highest + 1, not count' => [['ITEM001', 'ITEM017'], 'ITEM018'];
        yield 'unordered identifiers' => [['ITEM005', 'ITEM002'], 'ITEM006'];
        yield 'ignores non-item identifiers' => [['test', 'ITEM001', 'section-1'], 'ITEM002'];
        yield 'ignores lowercase identifiers' => [['item001'], 'ITEM001'];
        yield 'numbers beyond three digits are not truncated' => [['ITEM1000'], 'ITEM1001'];
    }
}
