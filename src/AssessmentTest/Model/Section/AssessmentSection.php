<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Model\Section;

use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRefCollection;
use Qti3\AssessmentTest\Exception\InvalidItemOrderException;
use Qti3\AssessmentTest\Exception\InvalidAssessmentTestException;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\IContentNode;
use Qti3\Shared\Model\QtiElement;

class AssessmentSection extends QtiElement
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $title,
        public readonly AssessmentItemRefCollection $assessmentItemRefs,
        public readonly ?Selection $selection = null,
        public readonly ?Ordering $ordering = null,
        public readonly bool $visible = true,
    ) {}

    public function addItemRef(AssessmentItemRef $itemRef): void
    {
        $this->assessmentItemRefs->add($itemRef);
    }

    /**
     * Rewrite the item refs of this section to match the given order.
     * @param list<string> $orderedIdentifiers
     */
    public function reorderItemRefs(array $orderedIdentifiers): void
    {
        $itemRefsByIdentifier = [];
        $currentIdentifiers = [];
        foreach ($this->assessmentItemRefs as $itemRef) {
            $identifier = (string) $itemRef->identifier;
            if (isset($itemRefsByIdentifier[$identifier])) {
                throw new InvalidAssessmentTestException(new StringCollection([sprintf('Assessment section has a duplicate item ref "%s"', $identifier)]));
            }
            $itemRefsByIdentifier[$identifier] = $itemRef;
            $currentIdentifiers[] = $identifier;
        }

        $this->assertOrderMatchesItemRefs($currentIdentifiers, $orderedIdentifiers);

        $this->assessmentItemRefs->replaceAll(array_map(
            static fn(string $identifier): AssessmentItemRef => $itemRefsByIdentifier[$identifier],
            $orderedIdentifiers,
        ));
    }

    /**
     * @param list<string> $currentIdentifiers
     * @param list<string> $orderedIdentifiers
     */
    private function assertOrderMatchesItemRefs(array $currentIdentifiers, array $orderedIdentifiers): void
    {
        $errors = [];

        foreach ($this->duplicates($orderedIdentifiers) as $duplicate) {
            $errors[] = sprintf('Item "%s" is listed more than once.', $duplicate);
        }

        foreach (array_diff($orderedIdentifiers, $currentIdentifiers) as $unknown) {
            $errors[] = sprintf('Item "%s" does not exist in the test.', $unknown);
        }

        foreach (array_diff($currentIdentifiers, $orderedIdentifiers) as $missing) {
            $errors[] = sprintf('Item "%s" is missing from the new order.', $missing);
        }

        if ($errors !== []) {
            throw new InvalidItemOrderException(new StringCollection($errors));
        }
    }

    /**
     * @param list<string> $identifiers
     * @return list<string>
     */
    private function duplicates(array $identifiers): array
    {
        $counts = array_count_values($identifiers);

        return array_map('strval', array_keys(array_filter($counts, static fn(int $count): bool => $count > 1)));
    }

    /**
     * @return array<string,string|null>
     */
    public function attributes(): array
    {
        return [
            'identifier' => $this->identifier,
            'title' => $this->title,
            'visible' => $this->visible ? 'true' : 'false',
        ];
    }

    /**
     * @return array<int,IContentNode|null>
     */
    public function children(): array
    {
        return [
            $this->selection,
            $this->ordering,
            ...$this->assessmentItemRefs->all(),
        ];
    }
}
