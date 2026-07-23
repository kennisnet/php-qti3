<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

/**
 * Generates the next sequential webcontent resource identifier (`RESOURCE001`,
 * `RESOURCE002`, ...).
 *
 * Gap-safe: the next number is derived from the highest existing `RESOURCEnnn`
 * number plus one, never from the count, so gaps in the numbering never produce
 * a colliding identifier. Mirrors {@see \Qti3\AssessmentItem\Service\ItemIdentifierGenerator}.
 */
final class WebcontentIdentifierGenerator
{
    private const string FORMAT = 'RESOURCE%03d';
    private const string PATTERN = '/^RESOURCE(\d+)$/';

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
