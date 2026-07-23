<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use DOMElement;
use DOMNode;
use DOMText;
use Qti3\AssessmentItem\Model\Feedback\FeedbackInline;
use Qti3\AssessmentItem\Model\Feedback\Visibility;
use Qti3\AssessmentItem\Model\Interaction\ChoiceInteraction\ChoiceInteraction;
use Qti3\AssessmentItem\Model\Interaction\ChoiceInteraction\SimpleChoice;
use Qti3\AssessmentItem\Model\Interaction\ExtendedTextInteraction\ExtendedTextInteraction;
use Qti3\AssessmentItem\Model\Interaction\GapMatchInteraction\Gap;
use Qti3\AssessmentItem\Model\Interaction\GapMatchInteraction\GapMatchInteraction;
use Qti3\AssessmentItem\Model\Interaction\GapMatchInteraction\GapText;
use Qti3\AssessmentItem\Model\Interaction\HotspotInteraction\HotspotChoice;
use Qti3\AssessmentItem\Model\Interaction\HotspotInteraction\HotspotInteraction;
use Qti3\AssessmentItem\Model\Interaction\HottextInteraction\Hottext;
use Qti3\AssessmentItem\Model\Interaction\HottextInteraction\HottextInteraction;
use Qti3\AssessmentItem\Model\Interaction\InlineChoiceInteraction\InlineChoice;
use Qti3\AssessmentItem\Model\Interaction\InlineChoiceInteraction\InlineChoiceInteraction;
use Qti3\AssessmentItem\Model\Interaction\InlineChoiceInteraction\Label;
use Qti3\AssessmentItem\Model\Interaction\MatchInteraction\MatchInteraction;
use Qti3\AssessmentItem\Model\Interaction\MatchInteraction\SimpleAssociableChoice;
use Qti3\AssessmentItem\Model\Interaction\MatchInteraction\SimpleMatchSet;
use Qti3\AssessmentItem\Model\Interaction\OrderInteraction\OrderInteraction;
use Qti3\AssessmentItem\Model\Interaction\OrderInteraction\Orientation;
use Qti3\AssessmentItem\Model\Interaction\Prompt;
use Qti3\AssessmentItem\Model\Interaction\SelectPointInteraction\SelectPointInteraction;
use Qti3\AssessmentItem\Model\Interaction\TextEntryInteraction\TextEntryInteraction;
use Qti3\AssessmentItem\Model\Shape\ShapeFactory;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\IXmlElement;
use Qti3\Shared\Model\TextNode;

class InteractionParser extends AbstractParser
{
    public function parse(DOMElement $element): IXmlElement
    {
        return match ($element->nodeName) {
            ChoiceInteraction::qtiTagName() => $this->parseChoiceInteraction($element),
            TextEntryInteraction::qtiTagName() => $this->parseTextEntryInteraction($element),
            ExtendedTextInteraction::qtiTagName() => $this->parseExtendedTextInteraction($element),
            GapMatchInteraction::qtiTagName() => $this->parseGapMatchInteraction($element),
            HotspotInteraction::qtiTagName() => $this->parseHotspotInteraction($element),
            HottextInteraction::qtiTagName() => $this->parseHottextInteraction($element),
            InlineChoiceInteraction::qtiTagName() => $this->parseInlineChoiceInteraction($element),
            MatchInteraction::qtiTagName() => $this->parseMatchInteraction($element),
            OrderInteraction::qtiTagName() => $this->parseOrderInteraction($element),
            SelectPointInteraction::qtiTagName() => $this->parseSelectPointInteraction($element),
            default => throw new ParseError('Unsupported interaction: ' . $element->nodeName),
        };
    }

    private function parseChoiceInteraction(DOMElement $element): ChoiceInteraction
    {
        $this->validateTag($element, ChoiceInteraction::qtiTagName());

        $responseIdentifier = $element->getAttribute('response-identifier') ?: 'RESPONSE';
        $shuffle = strtolower($element->getAttribute('shuffle')) === 'true';
        $maxChoicesAttr = $element->getAttribute('max-choices');
        $maxChoices = $maxChoicesAttr !== '' ? (int) $maxChoicesAttr : 1;
        $minChoices = $this->intAttributeOrNull($element, 'min-choices');
        $orientation = $this->orientationOrNull($element);

        $prompt = $this->findPrompt($element);

        $choices = [];
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === SimpleChoice::qtiTagName()) {
                $choices[] = $this->parseSimpleChoice($child);
            }
        }

        $shared = $this->readSharedAttributes($element, ['response-identifier', 'shuffle', 'max-choices', 'min-choices', 'orientation']);

        return new ChoiceInteraction($choices, $responseIdentifier, $prompt, $shuffle, $maxChoices, $minChoices, $orientation, $shared);
    }

    private function parseSimpleChoice(DOMElement $element): SimpleChoice
    {
        $this->validateTag($element, SimpleChoice::qtiTagName());
        $identifier = $element->getAttribute('identifier');
        $fixed = strtolower($element->getAttribute('fixed')) === 'true';
        $templateIdentifier = $element->getAttribute('template-identifier') ?: null;
        $showHide = $element->getAttribute('show-hide') ?: 'show';
        $content = $this->parseContentChildren($element);

        $feedback = null;
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === FeedbackInline::qtiTagName()) {
                $feedback = $this->parseFeedbackInline($child);
            }
        }

        $shared = $this->readSharedAttributes($element, ['identifier', 'fixed', 'template-identifier', 'show-hide']);

        return new SimpleChoice($identifier, $content, $feedback, $fixed, $templateIdentifier, $showHide, $shared);
    }

    private function parseFeedbackInline(DOMElement $element): FeedbackInline
    {
        $this->validateTag($element, FeedbackInline::qtiTagName());
        $identifier = $element->getAttribute('identifier');
        $outcomeIdentifier = $element->getAttribute('outcome-identifier') ?: 'FEEDBACK';
        $showHide = $element->getAttribute('show-hide') ?: Visibility::SHOW->value;
        $visibility = Visibility::from($showHide);

        $content = $this->parseContentChildren($element);
        return new FeedbackInline($identifier, $content, $outcomeIdentifier, $visibility);
    }

    private function parseTextEntryInteraction(DOMElement $element): TextEntryInteraction
    {
        $this->validateTag($element, TextEntryInteraction::qtiTagName());
        $responseIdentifier = $element->getAttribute('response-identifier') ?: 'RESPONSE';
        $shared = $this->readSharedAttributes($element, [
            'response-identifier', 'base', 'string-identifier', 'expected-length',
            'pattern-mask', 'placeholder-text', 'format',
        ]);

        return new TextEntryInteraction(
            $responseIdentifier,
            $this->intAttributeOrNull($element, 'base'),
            $element->getAttribute('string-identifier') ?: null,
            $this->intAttributeOrNull($element, 'expected-length'),
            $element->getAttribute('pattern-mask') ?: null,
            $element->getAttribute('placeholder-text') ?: null,
            $element->getAttribute('format') ?: null,
            $shared,
        );
    }

    private function parseExtendedTextInteraction(DOMElement $element): ExtendedTextInteraction
    {
        $this->validateTag($element, ExtendedTextInteraction::qtiTagName());
        $responseIdentifier = $element->getAttribute('response-identifier') ?: 'RESPONSE';
        $prompt = $this->findPrompt($element);
        $shared = $this->readSharedAttributes($element, [
            'response-identifier', 'base', 'string-identifier', 'expected-length', 'pattern-mask',
            'placeholder-text', 'max-strings', 'min-strings', 'expected-lines', 'format',
        ]);

        return new ExtendedTextInteraction(
            $responseIdentifier,
            $prompt,
            $this->intAttributeOrNull($element, 'base'),
            $element->getAttribute('string-identifier') ?: null,
            $this->intAttributeOrNull($element, 'expected-length'),
            $element->getAttribute('pattern-mask') ?: null,
            $element->getAttribute('placeholder-text') ?: null,
            $this->intAttributeOrNull($element, 'max-strings'),
            $this->intAttributeOrNull($element, 'min-strings'),
            $this->intAttributeOrNull($element, 'expected-lines'),
            $element->getAttribute('format') ?: null,
            $shared,
        );
    }

    private function parseGapMatchInteraction(DOMElement $element): GapMatchInteraction
    {
        $this->validateTag($element, GapMatchInteraction::qtiTagName());
        $responseIdentifier = $element->getAttribute('response-identifier') ?: 'RESPONSE';
        $shuffle = strtolower($element->getAttribute('shuffle')) === 'true';
        $maxAssoc = $element->getAttribute('max-associations');
        $minAssoc = $element->getAttribute('min-associations');
        $prompt = $this->findPrompt($element);

        $content = $this->parseContentChildren($element);
        $shared = $this->readSharedAttributes($element, ['response-identifier', 'shuffle', 'max-associations', 'min-associations']);

        return new GapMatchInteraction(
            $content,
            $responseIdentifier,
            $prompt,
            $shuffle,
            $maxAssoc !== '' ? (int) $maxAssoc : 0,
            $minAssoc !== '' ? (int) $minAssoc : null,
            $shared,
        );
    }

    private function parseHotspotInteraction(DOMElement $element): HotspotInteraction
    {
        $this->validateTag($element, HotspotInteraction::qtiTagName());
        $responseIdentifier = $element->getAttribute('response-identifier') ?: 'RESPONSE';
        $maxChoices = (int) ($element->getAttribute('max-choices') ?: '0');
        $minChoices = $this->intAttributeOrNull($element, 'min-choices');

        $image = null;
        $choices = [];
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === 'img') {
                $image = $this->parseHtmlElement($child);
            }
            if ($child->nodeName === HotspotChoice::qtiTagName()) {
                $choices[] = $this->parseHotspotChoice($child);
            }
        }

        if ($image === null) {
            throw new ParseError('HotspotInteraction must contain an img element');
        }

        $shared = $this->readSharedAttributes($element, ['response-identifier', 'max-choices', 'min-choices']);

        return new HotspotInteraction($image, $choices, $maxChoices, $responseIdentifier, $minChoices, $shared);
    }

    private function parseHotspotChoice(DOMElement $element): HotspotChoice
    {
        $shapeName = $element->getAttribute('shape') ?: 'default';
        $coords = $element->getAttribute('coords') ?: '';
        $shape = ShapeFactory::create($shapeName, $coords);
        $shared = $this->readSharedAttributes($element, ['shape', 'coords', 'identifier', 'template-identifier', 'show-hide', 'hotspot-label']);

        return new HotspotChoice(
            $shape,
            $element->getAttribute('identifier'),
            $element->getAttribute('template-identifier') ?: null,
            $element->getAttribute('show-hide') ?: 'show',
            $element->getAttribute('hotspot-label') ?: null,
            $shared,
        );
    }

    private function parseHottextInteraction(DOMElement $element): HottextInteraction
    {
        $this->validateTag($element, HottextInteraction::qtiTagName());
        $responseIdentifier = $element->getAttribute('response-identifier') ?: 'RESPONSE';
        $maxChoices = (int) ($element->getAttribute('max-choices') ?: '0');
        $minChoices = $this->intAttributeOrNull($element, 'min-choices');
        $content = $this->parseContentChildren($element);
        $shared = $this->readSharedAttributes($element, ['response-identifier', 'max-choices', 'min-choices']);

        return new HottextInteraction($maxChoices, $content, $responseIdentifier, $minChoices, $shared);
    }

    private function parseInlineChoiceInteraction(DOMElement $element): InlineChoiceInteraction
    {
        $this->validateTag($element, InlineChoiceInteraction::qtiTagName());
        $responseIdentifier = $element->getAttribute('response-identifier') ?: 'RESPONSE';
        $shuffle = strtolower($element->getAttribute('shuffle')) === 'true';
        $required = strtolower($element->getAttribute('required')) === 'true';
        $minChoices = $this->intAttributeOrNull($element, 'min-choices');

        $choices = [];
        $label = null;
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === InlineChoice::qtiTagName()) {
                $choices[] = $this->parseInlineChoice($child);
            }
            if ($child->nodeName === Label::qtiTagName()) {
                $label = new Label($this->parseContentChildren($child), $this->readSharedAttributes($child, []));
            }
        }

        $shared = $this->readSharedAttributes($element, ['response-identifier', 'shuffle', 'required', 'min-choices']);

        return new InlineChoiceInteraction($choices, $responseIdentifier, $shuffle, $required, $minChoices, $label, $shared);
    }

    private function parseInlineChoice(DOMElement $element): InlineChoice
    {
        $this->validateTag($element, InlineChoice::qtiTagName());
        $identifier = $element->getAttribute('identifier');
        $fixed = strtolower($element->getAttribute('fixed')) === 'true';
        $templateIdentifier = $element->getAttribute('template-identifier') ?: null;
        $showHide = $element->getAttribute('show-hide') ?: 'show';
        $content = $this->parseContentChildren($element);
        $shared = $this->readSharedAttributes($element, ['identifier', 'fixed', 'template-identifier', 'show-hide']);

        return new InlineChoice($identifier, $content, $fixed, $templateIdentifier, $showHide, $shared);
    }

    private function parseMatchInteraction(DOMElement $element): MatchInteraction
    {
        $this->validateTag($element, MatchInteraction::qtiTagName());
        $responseIdentifier = $element->getAttribute('response-identifier') ?: 'RESPONSE';
        $shuffle = strtolower($element->getAttribute('shuffle')) === 'true';
        $maxAssociations = $this->intAttributeOrNull($element, 'max-associations');
        $minAssociations = $this->intAttributeOrNull($element, 'min-associations');
        $prompt = $this->findPrompt($element);

        $sets = [];
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === SimpleMatchSet::qtiTagName()) {
                $sets[] = $this->parseSimpleMatchSet($child);
            }
        }
        $set1 = $sets[0] ?? new SimpleMatchSet([]);
        $set2 = $sets[1] ?? new SimpleMatchSet([]);

        $shared = $this->readSharedAttributes($element, ['response-identifier', 'shuffle', 'max-associations', 'min-associations']);

        return new MatchInteraction($set1, $set2, $prompt, $responseIdentifier, $shuffle, $maxAssociations, $minAssociations, $shared);
    }

    private function parseSimpleMatchSet(DOMElement $element): SimpleMatchSet
    {
        $this->validateTag($element, SimpleMatchSet::qtiTagName());
        $choices = [];
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === SimpleAssociableChoice::qtiTagName()) {
                $choices[] = $this->parseSimpleAssociableChoice($child);
            }
        }

        // A qti-simple-match-set only permits id (plus data-*); it does not extend the ARIA base.
        $shared = $this->readSharedAttributes($element, [], ['id'], allowAria: false);

        return new SimpleMatchSet($choices, $shared);
    }

    private function parseSimpleAssociableChoice(DOMElement $element): SimpleAssociableChoice
    {
        $matchMaxAttr = $element->getAttribute('match-max');
        $content = $this->parseContentChildren($element);
        $shared = $this->readSharedAttributes($element, ['identifier', 'match-max', 'match-min', 'fixed', 'template-identifier', 'show-hide', 'match-group']);

        return new SimpleAssociableChoice(
            $element->getAttribute('identifier'),
            $content,
            $matchMaxAttr !== '' ? (int) $matchMaxAttr : 1,
            $this->intAttributeOrNull($element, 'match-min'),
            strtolower($element->getAttribute('fixed')) === 'true',
            $element->getAttribute('template-identifier') ?: null,
            $element->getAttribute('show-hide') ?: null,
            $element->getAttribute('match-group') ?: null,
            $shared,
        );
    }

    private function parseOrderInteraction(DOMElement $element): OrderInteraction
    {
        $this->validateTag($element, OrderInteraction::qtiTagName());
        $responseIdentifier = $element->getAttribute('response-identifier') ?: 'RESPONSE';
        $shuffle = strtolower($element->getAttribute('shuffle')) === 'true';
        $orientationAttr = $element->getAttribute('orientation') ?: Orientation::VERTICAL->value;
        $orientation = Orientation::from($orientationAttr);
        $prompt = $this->findPrompt($element);

        $choices = [];
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === SimpleChoice::qtiTagName()) {
                $choices[] = $this->parseSimpleChoice($child);
            }
        }

        $shared = $this->readSharedAttributes($element, ['response-identifier', 'shuffle', 'orientation', 'min-choices', 'max-choices']);

        return new OrderInteraction(
            $choices,
            $responseIdentifier,
            $orientation,
            $shuffle,
            $prompt,
            $this->intAttributeOrNull($element, 'min-choices'),
            $this->intAttributeOrNull($element, 'max-choices'),
            $shared,
        );
    }

    private function parseSelectPointInteraction(DOMElement $element): SelectPointInteraction
    {
        $this->validateTag($element, SelectPointInteraction::qtiTagName());
        $responseIdentifier = $element->getAttribute('response-identifier') ?: 'RESPONSE';
        $maxChoices = (int) ($element->getAttribute('max-choices') ?: '0');
        $minChoices = $this->intAttributeOrNull($element, 'min-choices');
        $prompt = $this->findPrompt($element);

        $image = null;
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === 'img') {
                $image = $this->parseHtmlElement($child);
            }
        }

        if ($image === null) {
            throw new ParseError('SelectPointInteraction must contain an img element');
        }

        $shared = $this->readSharedAttributes($element, ['response-identifier', 'max-choices', 'min-choices']);

        return new SelectPointInteraction($image, $maxChoices, $prompt, $responseIdentifier, $minChoices, $shared);
    }

    private function findPrompt(DOMElement $element): ?Prompt
    {
        foreach ($this->getChildren($element) as $child) {
            if ($child->nodeName === Prompt::qtiTagName()) {
                $content = $this->parseContentChildren($child);
                return new Prompt($content, $this->readSharedAttributes($child, []));
            }
        }
        return null;
    }

    private function intAttributeOrNull(DOMElement $element, string $name): ?int
    {
        $value = $element->getAttribute($name);
        return $value !== '' ? (int) $value : null;
    }

    private function orientationOrNull(DOMElement $element): ?Orientation
    {
        $value = $element->getAttribute('orientation');
        return $value !== '' ? Orientation::from($value) : null;
    }

    private function parseContentChildren(DOMElement $element): ContentNodeCollection
    {
        $content = new ContentNodeCollection();
        foreach ($element->childNodes as $child) {
            $node = $this->parseContentNode($child);
            if ($node !== null) {
                $content->add($node);
            }
        }
        return $content;
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
            // Recognise known QTI inline elements before falling through to HTMLTag
            if ($node->nodeName === Hottext::qtiTagName()) {
                return new Hottext(
                    $node->getAttribute('identifier'),
                    $this->parseContentChildren($node),
                    $node->getAttribute('template-identifier') ?: null,
                    $node->getAttribute('show-hide') ?: 'show',
                    $this->readSharedAttributes($node, ['identifier', 'template-identifier', 'show-hide']),
                );
            }
            if ($node->nodeName === Gap::qtiTagName()) {
                return new Gap(
                    $node->getAttribute('identifier'),
                    $node->getAttribute('template-identifier') ?: null,
                    $node->getAttribute('show-hide') ?: 'show',
                    $node->getAttribute('match-group') ?: null,
                    strtolower($node->getAttribute('required')) === 'true',
                    $this->readSharedAttributes($node, ['identifier', 'template-identifier', 'show-hide', 'match-group', 'required']),
                );
            }
            if ($node->nodeName === GapText::qtiTagName()) {
                $matchMax = (int) ($node->getAttribute('match-max') ?: '0');
                $matchMin = (int) ($node->getAttribute('match-min') ?: '0');
                $matchGroup = $node->getAttribute('match-group') ?: null;
                $templateIdentifier = $node->getAttribute('template-identifier') ?: null;
                $showHide = $node->getAttribute('show-hide') ?: 'show';
                return new GapText(
                    $node->getAttribute('identifier'),
                    $matchMax,
                    $this->parseContentChildren($node),
                    $matchMin,
                    $matchGroup,
                    $templateIdentifier,
                    $showHide,
                    $this->readSharedAttributes($node, ['identifier', 'match-max', 'match-min', 'match-group', 'template-identifier', 'show-hide']),
                );
            }
            if ($node->nodeName === FeedbackInline::qtiTagName()) {
                return $this->parseFeedbackInline($node);
            }

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

    private function parseHtmlElement(DOMElement $element): HTMLTag
    {
        $attributes = [];
        foreach ($element->attributes as $attr) {
            $attributes[$attr->nodeName] = $attr->nodeValue;
        }
        $children = [];
        foreach ($element->childNodes as $child) {
            $parsedChild = $this->parseContentNode($child);
            if ($parsedChild !== null) {
                $children[] = $parsedChild;
            }
        }
        return new HTMLTag($element->nodeName, $attributes, $children);
    }
}
