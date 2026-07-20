<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service;

/**
 * Generates the next sequential assessment item identifier (`ITEM001`,
 * `ITEM002`, ...).
 *
 * Gap-safe: the next number is derived from the highest existing `ITEMnnn`
 * number plus one, never from the count, because packages can contain gaps in
 * their item numbering. Counting would otherwise produce a colliding identifier.
 *
 * TODO: if an item database/reuse-across-packages feature is built, items will
 * need identifiers that stay unique outside a single package's `ITEMnnn`
 * sequence, so this generation scheme will likely need to change.
 */
final class ItemIdentifierGenerator
{
    private const string FORMAT = 'ITEM%03d';
    private const string PATTERN = '/^ITEM(\d+)$/';

    /**
     * @param list<string> $existingIdentifiers
     */
    public function nextIdentifier(array $existingIdentifiers): string
    {
        $highest = 0;

        foreach ($existingIdentifiers as $identifier) {
            if (preg_match(self::PATTERN, $identifier, $matches) === 1) {
                $highest = max($highest, (int) $matches[1]);
            }
        }

        return sprintf(self::FORMAT, $highest + 1);
    }
}
