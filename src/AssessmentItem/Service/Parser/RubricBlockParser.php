<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use DOMElement;
use DOMNode;
use DOMText;
use Qti3\AssessmentItem\Model\RubricBlock\RubricBlock;
use Qti3\AssessmentItem\Model\RubricBlock\View;
use Qti3\AssessmentItem\Model\RubricBlock\ViewCollection;
use Qti3\AssessmentItem\Model\RubricBlock\qtiUse;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\TextNode;

class RubricBlockParser extends AbstractParser
{
    public function parse(DOMElement $element, ?StringCollection $warnings = null): RubricBlock
    {
        $this->validateTag($element, RubricBlock::qtiTagName());

        $use = $this->parseUse($element, $warnings);
        $views = $this->parseViews($element, $warnings);
        $class = $element->getAttribute('class') ?: null;

        $contentRoot = $this->unwrapContentBody($element);

        $content = new ContentNodeCollection();
        foreach ($contentRoot->childNodes as $child) {
            $node = $this->parseContentNode($child);
            if ($node !== null) {
                $content->add($node);
            }
        }

        return new RubricBlock($use, $views, new ContentBody($content), $class);
    }

    /** Optional in the schema, and open to `ext:` values the model cannot hold. */
    private function parseUse(DOMElement $element, ?StringCollection $warnings): ?qtiUse
    {
        $raw = trim($element->getAttribute('use'));
        if ($raw === '') {
            return null;
        }

        $use = qtiUse::tryFrom($raw);
        if ($use === null) {
            $warnings?->add(sprintf('%s: drops unsupported use "%s"', $this->locate($element), $raw));
        }

        return $use;
    }

    /** A required whitespace-separated list; at least one view must survive. */
    private function parseViews(DOMElement $element, ?StringCollection $warnings): ViewCollection
    {
        $raw = trim($element->getAttribute('view'));
        $tokens = $raw === '' ? [] : (preg_split('/\s+/', $raw) ?: []);

        $views = new ViewCollection();
        foreach ($tokens as $token) {
            $view = View::tryFrom($token);
            if ($view === null) {
                $warnings?->add(sprintf('%s: drops unknown view "%s"', $this->locate($element), $token));
                continue;
            }
            $views->add($view);
        }

        if ($views->isEmpty()) {
            throw new ParseError(sprintf(
                'qti-rubric-block requires at least one known view, got "%s"',
                $element->getAttribute('view'),
            ));
        }

        return $views;
    }

    private function parseContentNode(DOMNode $node): mixed
    {
        if ($node instanceof DOMText) {
            $text = $node->textContent;
            if (trim($text) === '') {
                return null;
            }
            return new TextNode($text);
        }

        if ($node instanceof DOMElement) {
            $attributes = [];
            foreach ($node->attributes as $attr) {
                $attributes[$attr->nodeName] = $attr->nodeValue;
            }
            $children = [];
            foreach ($node->childNodes as $child) {
                $parsedChild = $this->parseContentNode($child);
                if ($parsedChild !== null) {
                    $children[] = $parsedChild;
                }
            }
            return new HTMLTag($node->nodeName, $attributes, $children);
        }

        return null;
    }
}
