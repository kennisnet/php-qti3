<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service;

use DOMElement;
use Qti3\AssessmentItem\Model\ResponseProcessing\ResponseProcessing;
use Qti3\Shared\Exception\UnsupportedQtiConstructException;
use Qti3\Shared\Collection\StringCollection;

/**
 * Guards the typed-model editing flow: asserts that an assessment item XML
 * document only contains top-level constructs the
 * {@see \Qti3\AssessmentItem\Model\AssessmentItem} model and its parsers can
 * represent, because regenerating the package from the model silently drops
 * everything else (template declarations, unknown response processing
 * templates, extra stylesheets, ...).
 *
 * Constructs the parsers reject themselves (unsupported interaction types,
 * disallowed HTML) already fail loudly with a parse error and need no
 * checking here. The allowlists must mirror what AssessmentItemParser and
 * ResponseProcessingParser read; extend them together.
 */
final class AssessmentItemSupportValidator
{
    /** @var list<string> */
    private const array ITEM_CHILDREN = [
        'qti-response-declaration',
        'qti-outcome-declaration',
        'qti-item-body',
        'qti-response-processing',
        'qti-stylesheet',
        'qti-modal-feedback',
    ];

    /** @var list<string> */
    private const array SUPPORTED_RESPONSE_PROCESSING_TEMPLATES = [
        ResponseProcessing::TEMPLATE_MATCH_CORRECT,
        ResponseProcessing::TEMPLATE_MAP_RESPONSE,
        ResponseProcessing::TEMPLATE_MAP_RESPONSE_POINT,
    ];

    /**
     * @throws UnsupportedQtiConstructException
     */
    public function assertSupported(DOMElement $item): void
    {
        $errors = [];
        $stylesheetCount = 0;

        foreach ($this->childElements($item) as $itemChild) {
            if (!in_array($itemChild->localName, self::ITEM_CHILDREN, true)) {
                $errors[] = sprintf('Assessment item contains unsupported element <%s>', $itemChild->localName);
                continue;
            }
            if ($itemChild->localName === 'qti-stylesheet' && ++$stylesheetCount > 1) {
                $errors[] = 'Assessment item contains more than one stylesheet';
            }
            if (
                $itemChild->localName === 'qti-response-processing'
                && $itemChild->hasAttribute('template')
                && !in_array($itemChild->getAttribute('template'), self::SUPPORTED_RESPONSE_PROCESSING_TEMPLATES, true)
            ) {
                $errors[] = sprintf('Assessment item uses unsupported response processing template "%s"', $itemChild->getAttribute('template'));
            }
        }

        if ($errors !== []) {
            throw new UnsupportedQtiConstructException(new StringCollection($errors));
        }
    }

    /**
     * @return list<DOMElement>
     */
    private function childElements(DOMElement $element): array
    {
        $children = [];
        foreach ($element->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $children[] = $childNode;
            }
        }

        return $children;
    }
}
