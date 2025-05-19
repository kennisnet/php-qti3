<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentTest\Model;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItem;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItemId;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\RubricBlock\RubricBlock;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\Feedback\TestFeedbackCollection;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\ItemRef\AssessmentItemRef;
use App\SharedKernel\Domain\Qti\AssessmentTest\Model\TestPart\TestPartCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\IContentNode;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeProcessing\OutcomeProcessing;
use App\SharedKernel\Domain\Qti\Shared\Model\QtiElement;
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
            'identifier' => '_' . $this->identifier,
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
            if ($itemRef->itemId && $itemRef->itemId->equals($assessmentItemId)) {
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
