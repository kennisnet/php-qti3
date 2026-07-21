<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Model;

use Qti3\AssessmentItem\Model\AssessmentItem;
use Qti3\AssessmentItem\Model\AssessmentItemId;
use Qti3\AssessmentItem\Model\RubricBlock\RubricBlock;
use Qti3\AssessmentTest\Model\Feedback\TestFeedbackCollection;
use Qti3\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use Qti3\AssessmentTest\Model\Section\AssessmentSection;
use Qti3\AssessmentTest\Model\TestPart\TestPartCollection;
use Qti3\AssessmentTest\Exception\InvalidAssessmentTestException;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\IContentNode;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use Qti3\AssessmentTest\Model\OutcomeProcessing\OutcomeProcessing;
use Qti3\Shared\Model\QtiElement;
use RuntimeException;

class AssessmentTest extends QtiElement
{
    public function __construct(
        public readonly AssessmentTestId $identifier,
        public readonly OutcomeDeclarationCollection $outcomeDeclarations,
        public readonly TestPartCollection $testParts,
        public readonly ?string $title = null,
        public readonly ?OutcomeProcessing $outcomeProcessing = null,
        public readonly TestFeedbackCollection $testFeedback = new TestFeedbackCollection(),
        public readonly ?RubricBlock $rubricBlock = null,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function attributes(): array
    {
        return [
            'identifier' => (string) $this->identifier,
            'title' => $this->title,
            'xmlns' => 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0',
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0 https://purl.imsglobal.org/spec/qti/v3p0/schema/xsd/imsqti_asiv3p0_v1p0.xsd',
        ];
    }

    /**
     * @return array<int,IContentNode|null>
     */
    public function children(): array
    {
        return [
            ...$this->outcomeDeclarations->all(),
            $this->rubricBlock,
            ...$this->testParts->all(),
            $this->outcomeProcessing,
            ...$this->testFeedback->all(),
        ];
    }

    /**
     * Add an item ref to the first section. Position -1 appends; a zero-based
     * position inserts at that index.
     */
    public function addItemRef(AssessmentItemRef $itemRef, int $position = -1): void
    {
        foreach ($this->getItemRefs() as $existingItemRef) {
            if ($existingItemRef->identifier->equals($itemRef->identifier)) {
                throw new InvalidAssessmentTestException(new StringCollection([sprintf('Test already contains an item with identifier "%s"', $itemRef->identifier)]));
            }
        }

        $this->getSection()->addItemRef($itemRef, $position);
    }

    /**
     * Reorder the item refs of the first section to match the given order.
     * @param list<string> $orderedIdentifiers
     */
    public function reorderItemRefs(array $orderedIdentifiers): void
    {
        $this->getSection()->reorderItemRefs($orderedIdentifiers);
    }

    /**
     * @return list<string>
     */
    public function getItemIdentifiers(): array
    {
        return array_map(
            static fn(AssessmentItemRef $itemRef): string => (string) $itemRef->identifier,
            $this->getItemRefs(),
        );
    }

    private function getSection(): AssessmentSection
    {
        $section = $this->testParts->first()?->sections->first();
        if (!$section instanceof AssessmentSection) {
            throw new InvalidAssessmentTestException(new StringCollection(['Assessment test has no section']));
        }

        return $section;
    }

    /**
     * @return array<int, AssessmentItemRef>
     */
    public function getItemRefs(): array
    {
        $itemRefs = [];
        foreach ($this->testParts as $testPart) {
            $itemRefs = [...$itemRefs, ...$testPart->getItemRefs()];
        }

        return $itemRefs;
    }

    public function findItemRef(AssessmentItemId $assessmentItemId): AssessmentItemRef
    {
        foreach ($this->getItemRefs() as $itemRef) {
            if ($itemRef->identifier->equals($assessmentItemId)) {
                return $itemRef;
            }
        }
        throw new RuntimeException(sprintf('Item with identifier %s not found in test', $assessmentItemId));
    }

    /**
     * @param array<int,AssessmentItem> $assessmentItems
     */
    public function validateItems(array $assessmentItems): void
    {
        if (count($this->getItemRefs()) !== count($assessmentItems)) {
            throw new RuntimeException('Number of items does not match number of itemRefs in test');
        }
    }
}
