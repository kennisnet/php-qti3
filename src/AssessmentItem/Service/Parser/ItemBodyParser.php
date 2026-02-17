<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use Qti3\AssessmentItem\Model\Interaction\ChoiceInteraction\ChoiceInteraction;
use Qti3\AssessmentItem\Model\Interaction\ExtendedTextInteraction\ExtendedTextInteraction;
use Qti3\AssessmentItem\Model\Interaction\GapMatchInteraction\GapMatchInteraction;
use Qti3\AssessmentItem\Model\Interaction\HotspotInteraction\HotspotInteraction;
use Qti3\AssessmentItem\Model\Interaction\HottextInteraction\HottextInteraction;
use Qti3\AssessmentItem\Model\Interaction\MatchInteraction\MatchInteraction;
use Qti3\AssessmentItem\Model\Interaction\OrderInteraction\OrderInteraction;
use Qti3\AssessmentItem\Model\Interaction\SelectPointInteraction\SelectPointInteraction;
use Qti3\AssessmentItem\Model\Interaction\TextEntryInteraction\TextEntryInteraction;
use Qti3\AssessmentItem\Model\ItemBody;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\TextNode;
use DOMElement;
use DOMNode;
use DOMText;

class ItemBodyParser extends AbstractParser
{
    public function __construct(private readonly ?InteractionParser $interactionParser = null) {}

    public function parse(DOMElement $element): ItemBody
    {
        $this->validateTag($element, ItemBody::qtiTagName());

        $content = new ContentNodeCollection();
        foreach ($element->childNodes as $child) {
            $node = $this->parseNode($child);
            if ($node !== null) {
                $content->add($node);
            }
        }

        return new ItemBody($content);
    }

    private function parseNode(DOMNode $node): mixed
    {
        if ($node instanceof DOMText) {
            $text = $node->textContent;
            if (trim($text) === '') {
                return null;
            }
            return new TextNode($text);
        }

        if ($node instanceof DOMElement) {
            $tagName = $node->nodeName;

            // Interactions: delegate to InteractionParser when available
            $interactionTags = [
                ChoiceInteraction::qtiTagName(),
                TextEntryInteraction::qtiTagName(),
                ExtendedTextInteraction::qtiTagName(),
                GapMatchInteraction::qtiTagName(),
                HotspotInteraction::qtiTagName(),
                HottextInteraction::qtiTagName(),
                MatchInteraction::qtiTagName(),
                OrderInteraction::qtiTagName(),
                SelectPointInteraction::qtiTagName(),
            ];
            if (in_array($tagName, $interactionTags, true) && $this->interactionParser !== null) {
                return $this->interactionParser->parse($node);
            }

            // Default: treat as HTML content
            $attributes = [];
            foreach ($node->attributes as $attr) {
                $attributes[$attr->nodeName] = $attr->nodeValue;
            }

            $children = [];
            foreach ($node->childNodes as $child) {
                $parsedChild = $this->parseNode($child);
                if ($parsedChild !== null) {
                    $children[] = $parsedChild;
                }
            }

            return new HTMLTag($tagName, $attributes, $children);
        }

        return null;
    }
}
