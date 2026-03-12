<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service;

use Qti3\AssessmentItem\Model\Interaction\ExtendedTextInteraction\ExtendedTextInteraction;
use DOMDocument;

class AssessmentItemDeterminator
{
    public function determineType(DOMDocument $itemXml): string
    {
        if ($this->hasInteraction($itemXml)) {
            return 'question';
        }

        return 'info';
    }

    private function hasInteraction(DOMDocument $itemXml): bool
    {
        foreach ($itemXml->getElementsByTagName('*') as $element) {
            $tagName = $element->localName ?? $element->nodeName;
            if (str_starts_with($tagName, 'qti-') && str_ends_with($tagName, '-interaction')) {
                return true;
            }
        }

        return false;
    }

    public function determineManualScoring(DOMDocument $itemXml): bool
    {
        $hasExtendedTextInteraction = $itemXml->getElementsByTagName(ExtendedTextInteraction::qtiTagName())->length > 0;

        return $hasExtendedTextInteraction;
    }

    public function determineTitle(DOMDocument $itemXml): string
    {
        return $itemXml->documentElement?->getAttribute('title') ?? '';
    }
}
