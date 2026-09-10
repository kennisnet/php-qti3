<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use Qti3\AssessmentItem\Model\Feedback\FeedbackBlock;
use Qti3\AssessmentItem\Model\ItemBody;
use Qti3\AssessmentItem\Model\RubricBlock\RubricBlock;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\TextNode;
use DOMElement;
use DOMNode;
use DOMText;

class ItemBodyParser extends AbstractParser
{
    public function __construct(
        private readonly InteractionParser $interactionParser,
        private readonly RubricBlockParser $rubricBlockParser,
        private readonly FeedbackBlockParser $feedbackBlockParser,
    ) {}

    /**
     * Parse a `qti-item-body`. `$warnings` is handed to the child parsers that
     * report constructs they cannot represent, so item-level warnings reach the
     * {@see ItemParseResult} like the test-level ones do.
     */
    public function parse(DOMElement $element, ?StringCollection $warnings = null): ItemBody
    {
        $this->validateTag($element, ItemBody::qtiTagName());

        $content = new ContentNodeCollection();
        foreach ($element->childNodes as $child) {
            $node = $this->parseNode($child, $warnings);
            if ($node !== null) {
                $content->add($node);
            }
        }

        return new ItemBody($content);
    }

    private function parseNode(DOMNode $node, ?StringCollection $warnings): mixed
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

            if (str_starts_with($tagName, 'qti-') && str_ends_with($tagName, '-interaction')) {
                return $this->interactionParser->parse($node);
            }

            if ($tagName === RubricBlock::qtiTagName()) {
                return $this->rubricBlockParser->parse($node, $warnings);
            }

            if ($tagName === FeedbackBlock::qtiTagName()) {
                return $this->feedbackBlockParser->parse($node);
            }

            // Default: treat as HTML content
            $attributes = [];
            foreach ($node->attributes as $attr) {
                $attributes[$attr->nodeName] = $attr->nodeValue;
            }

            $children = [];
            foreach ($node->childNodes as $child) {
                $parsedChild = $this->parseNode($child, $warnings);
                if ($parsedChild !== null) {
                    $children[] = $parsedChild;
                }
            }

            return new HTMLTag($tagName, $attributes, $children);
        }

        return null;
    }
}
