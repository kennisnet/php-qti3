<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Service\WebcontentIdentifierGenerator;

final class WebcontentIdentifierGeneratorTest extends TestCase
{
    /**
     * @param list<string> $existingIdentifiers
     */
    #[Test]
    #[DataProvider('identifierProvider')]
    public function itGeneratesTheNextSequentialIdentifier(array $existingIdentifiers, string $expected): void
    {
        $next = (new WebcontentIdentifierGenerator())->nextIdentifier($existingIdentifiers);

        $this->assertSame($expected, $next);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function identifierProvider(): iterable
    {
        yield 'empty package starts at RESOURCE001' => [[], 'RESOURCE001'];
        yield 'sequential identifiers' => [['RESOURCE001', 'RESOURCE002'], 'RESOURCE003'];
        yield 'gap-safe: uses highest + 1, not count' => [['RESOURCE001', 'RESOURCE017'], 'RESOURCE018'];
        yield 'unordered identifiers' => [['RESOURCE005', 'RESOURCE002'], 'RESOURCE006'];
        yield 'ignores non-webcontent identifiers' => [['ITEM001', 'RESOURCE001', 'test'], 'RESOURCE002'];
        yield 'numbers beyond three digits are not truncated' => [['RESOURCE1000'], 'RESOURCE1001'];
    }
}
