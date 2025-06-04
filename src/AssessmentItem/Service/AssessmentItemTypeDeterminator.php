<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Service;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Interaction\ExtendedTextInteraction\ExtendedTextInteraction;
use App\SharedKernel\Domain\Qti\Shared\Model\ResponseProcessing\ResponseProcessing;
use DOMDocument;

class AssessmentItemTypeDeterminator
{
    public function determine(DOMDocument $itemXml): string
    {
        $hasResponseProcessing = $itemXml->getElementsByTagName(ResponseProcessing::qtiTagName())->length > 0;
        $hasExtendedTextInteraction = $itemXml->getElementsByTagName(ExtendedTextInteraction::qtiTagName())->length > 0;

        if ($hasResponseProcessing || $hasExtendedTextInteraction) {
            return 'question';
        }

        return 'info';
    }
}
