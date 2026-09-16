<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Service\Parser;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\AssessmentItem\Model\Feedback\FeedbackInline;
use Qti3\AssessmentItem\Model\Feedback\Visibility;
use Qti3\AssessmentItem\Model\Interaction\ChoiceInteraction\ChoiceInteraction;
use Qti3\AssessmentItem\Model\Interaction\ExtendedTextInteraction\ExtendedTextInteraction;
use Qti3\AssessmentItem\Model\Interaction\GapMatchInteraction\Gap;
use Qti3\AssessmentItem\Model\Interaction\GapMatchInteraction\GapMatchInteraction;
use Qti3\AssessmentItem\Model\Interaction\GapMatchInteraction\GapText;
use Qti3\AssessmentItem\Model\Interaction\HotspotInteraction\HotspotInteraction;
use Qti3\AssessmentItem\Model\Interaction\HottextInteraction\Hottext;
use Qti3\AssessmentItem\Model\Interaction\HottextInteraction\HottextInteraction;
use Qti3\AssessmentItem\Model\Interaction\InlineChoiceInteraction\InlineChoiceInteraction;
use Qti3\AssessmentItem\Model\Interaction\InlineChoiceInteraction\Label;
use Qti3\AssessmentItem\Model\Interaction\MatchInteraction\MatchInteraction;
use Qti3\AssessmentItem\Model\Interaction\OrderInteraction\OrderInteraction;
use Qti3\AssessmentItem\Model\Interaction\OrderInteraction\Orientation;
use Qti3\AssessmentItem\Model\Interaction\SelectPointInteraction\SelectPointInteraction;
use Qti3\AssessmentItem\Model\Interaction\TextEntryInteraction\TextEntryInteraction;
use Qti3\AssessmentItem\Model\Shape\ShapeName;
use Qti3\AssessmentItem\Service\Parser\InteractionParser;
use Qti3\AssessmentItem\Service\Parser\ParseError;
use Qti3\Shared\Model\HTMLTag;
use Qti3\Shared\Model\TextNode;

class InteractionParserTest extends TestCase
{
    private InteractionParser $parser;

    protected function setUp(): void
    {
        $this->parser = new InteractionParser();
    }

    private function loadElement(string $xml): DOMElement
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc->documentElement;
    }

    #[Test]
    public function parseChoiceInteraction(): void
    {
        $element = $this->loadElement('
            <qti-choice-interaction response-identifier="RESPONSE_1" shuffle="true" max-choices="2">
                <qti-simple-choice identifier="A">Answer A</qti-simple-choice>
                <qti-simple-choice identifier="B">Answer B</qti-simple-choice>
                <qti-simple-choice identifier="C">Answer C</qti-simple-choice>
            </qti-choice-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(ChoiceInteraction::class, $result);
        $this->assertSame('RESPONSE_1', $result->responseIdentifier);
        $this->assertTrue($result->shuffle);
        $this->assertSame(2, $result->maxChoices);
        $this->assertCount(3, $result->choices);
        $this->assertSame('A', $result->choices[0]->identifier);
        $this->assertSame('B', $result->choices[1]->identifier);
        $this->assertSame('C', $result->choices[2]->identifier);
        $this->assertInstanceOf(TextNode::class, $result->choices[0]->content->all()[0]);
        $this->assertSame('Answer A', $result->choices[0]->content->all()[0]->content);
    }

    #[Test]
    public function parseChoiceInteractionWithPrompt(): void
    {
        $element = $this->loadElement('
            <qti-choice-interaction response-identifier="RESPONSE">
                <qti-prompt>Choose the correct answer</qti-prompt>
                <qti-simple-choice identifier="A">Answer A</qti-simple-choice>
            </qti-choice-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(ChoiceInteraction::class, $result);
        $this->assertNotNull($result->prompt);
        $this->assertSame('Choose the correct answer', $result->prompt->content->all()[0]->content);
        $this->assertCount(1, $result->choices);
    }

    #[Test]
    public function parseChoiceInteractionDefaults(): void
    {
        $element = $this->loadElement('
            <qti-choice-interaction>
                <qti-simple-choice identifier="A">Answer</qti-simple-choice>
            </qti-choice-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(ChoiceInteraction::class, $result);
        $this->assertSame('RESPONSE', $result->responseIdentifier);
        $this->assertFalse($result->shuffle);
        $this->assertSame(1, $result->maxChoices);
    }

    #[Test]
    public function parseTextEntryInteraction(): void
    {
        $element = $this->loadElement('
            <qti-text-entry-interaction response-identifier="RESPONSE_TEXT"/>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(TextEntryInteraction::class, $result);
        $this->assertSame('RESPONSE_TEXT', $result->responseIdentifier);
    }

    #[Test]
    public function parseExtendedTextInteraction(): void
    {
        $element = $this->loadElement('
            <qti-extended-text-interaction response-identifier="RESPONSE_EXT">
                <qti-prompt>Write your essay</qti-prompt>
            </qti-extended-text-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(ExtendedTextInteraction::class, $result);
        $this->assertSame('RESPONSE_EXT', $result->responseIdentifier);
        $this->assertNotNull($result->prompt);
        $this->assertSame('Write your essay', $result->prompt->content->all()[0]->content);
    }

    #[Test]
    public function parseGapMatchInteraction(): void
    {
        $element = $this->loadElement('
            <qti-gap-match-interaction response-identifier="RESPONSE_GAP" shuffle="true" max-associations="3" min-associations="1">
                <p>Fill in the blanks.</p>
            </qti-gap-match-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(GapMatchInteraction::class, $result);
        $this->assertSame('RESPONSE_GAP', $result->responseIdentifier);
        $this->assertTrue($result->shuffle);
        $this->assertSame(3, $result->maxAssociations);
        $this->assertSame(1, $result->minAssociations);
        $this->assertGreaterThan(0, count($result->content));
    }

    #[Test]
    public function parseHotspotInteraction(): void
    {
        $element = $this->loadElement('
            <qti-hotspot-interaction response-identifier="RESPONSE_HS" max-choices="2">
                <img src="map.png" alt="A map"/>
                <qti-hotspot-choice identifier="hs1" shape="rect" coords="0,0,100,100"/>
                <qti-hotspot-choice identifier="hs2" shape="circle" coords="200,200,50"/>
            </qti-hotspot-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(HotspotInteraction::class, $result);
        $this->assertSame('RESPONSE_HS', $result->responseIdentifier);
        $this->assertSame(2, $result->maxChoices);
        $this->assertSame('img', $result->image->tagName());
        $this->assertCount(2, $result->choices);
        $this->assertSame('hs1', $result->choices[0]->identifier);
        $this->assertSame(ShapeName::RECTANGLE, $result->choices[0]->shape->name());
        $this->assertSame('hs2', $result->choices[1]->identifier);
        $this->assertSame(ShapeName::CIRCLE, $result->choices[1]->shape->name());
    }

    #[Test]
    public function parseHotspotInteractionWithoutImage(): void
    {
        $element = $this->loadElement('
            <qti-hotspot-interaction response-identifier="RESPONSE_HS" max-choices="1">
                <qti-hotspot-choice identifier="hs1" shape="rect" coords="0,0,10,10"/>
            </qti-hotspot-interaction>
        ');

        $this->expectException(ParseError::class);
        $this->expectExceptionMessage('HotspotInteraction is missing a required <img> or <picture> element.');

        $this->parser->parse($element);
    }

    #[Test]
    public function parseHotspotInteractionWithPicture(): void
    {
        $element = $this->loadElement('
            <qti-hotspot-interaction response-identifier="RESPONSE_HS" max-choices="1">
                <picture>
                    <source srcset="map.webp" type="image/webp"/>
                    <img src="map.png" alt="A map"/>
                </picture>
                <qti-hotspot-choice identifier="hs1" shape="rect" coords="0,0,10,10"/>
            </qti-hotspot-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(HotspotInteraction::class, $result);
        $this->assertSame('picture', $result->image->tagName());
    }

    #[Test]
    public function parseHottextInteraction(): void
    {
        $element = $this->loadElement('
            <qti-hottext-interaction response-identifier="RESPONSE_HT" max-choices="2">
                <p>The cat sat on the mat.</p>
            </qti-hottext-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(HottextInteraction::class, $result);
        $this->assertSame('RESPONSE_HT', $result->responseIdentifier);
        $this->assertSame(2, $result->maxChoices);
        $this->assertGreaterThan(0, count($result->content));
    }

    #[Test]
    public function parseInlineChoiceInteraction(): void
    {
        $element = $this->loadElement('
            <qti-inline-choice-interaction response-identifier="RESPONSE_IC" shuffle="true" required="true" min-choices="1">
                <qti-inline-choice identifier="G">gaseous</qti-inline-choice>
                <qti-inline-choice identifier="L">liquid</qti-inline-choice>
                <qti-inline-choice identifier="S">solid</qti-inline-choice>
            </qti-inline-choice-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(InlineChoiceInteraction::class, $result);
        $this->assertSame('RESPONSE_IC', $result->responseIdentifier);
        $this->assertTrue($result->shuffle);
        $this->assertTrue($result->required);
        $this->assertSame(1, $result->minChoices);
        $this->assertCount(3, $result->choices);
        $this->assertSame('G', $result->choices[0]->identifier);
        $this->assertInstanceOf(TextNode::class, $result->choices[0]->content->all()[0]);
        $this->assertSame('gaseous', $result->choices[0]->content->all()[0]->content);
        $this->assertSame('L', $result->choices[1]->identifier);
        $this->assertSame('S', $result->choices[2]->identifier);
    }

    #[Test]
    public function parseInlineChoiceInteractionDefaults(): void
    {
        $element = $this->loadElement('
            <qti-inline-choice-interaction>
                <qti-inline-choice identifier="A">Alpha</qti-inline-choice>
            </qti-inline-choice-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(InlineChoiceInteraction::class, $result);
        $this->assertSame('RESPONSE', $result->responseIdentifier);
        $this->assertFalse($result->shuffle);
        $this->assertFalse($result->required);
        $this->assertCount(1, $result->choices);
        $choice = $result->choices[0];
        $this->assertSame('A', $choice->identifier);
        $this->assertFalse($choice->fixed);
        $this->assertNull($choice->templateIdentifier);
        $this->assertSame('show', $choice->showHide);
    }

    #[Test]
    public function parseInlineChoiceInteractionWithLabel(): void
    {
        $element = $this->loadElement('
            <qti-inline-choice-interaction response-identifier="RESPONSE">
                <qti-label class="lbl">Choose one</qti-label>
                <qti-inline-choice identifier="A">Alpha</qti-inline-choice>
            </qti-inline-choice-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(Label::class, $result->labelElement);
        $this->assertSame('lbl', $result->labelElement->class);
        $this->assertSame('Choose one', $result->labelElement->content->all()[0]->content);
        // The label precedes the choices, matching the schema's child sequence.
        $this->assertSame($result->labelElement, $result->children()[0]);
        $this->assertCount(1, $result->choices);
    }

    #[Test]
    public function parseInlineChoiceKeepsChoiceAttributes(): void
    {
        $element = $this->loadElement('
            <qti-inline-choice-interaction response-identifier="RESPONSE">
                <qti-inline-choice identifier="A" fixed="true" template-identifier="SHOW_A" show-hide="hide">Alpha</qti-inline-choice>
            </qti-inline-choice-interaction>
        ');

        $result = $this->parser->parse($element);

        $choice = $result->choices[0];
        $this->assertTrue($choice->fixed);
        $this->assertSame('SHOW_A', $choice->templateIdentifier);
        $this->assertSame('hide', $choice->showHide);
    }

    #[Test]
    public function keepsSpecAllowedAttributesAndDropsDisallowedOnes(): void
    {
        $element = $this->loadElement('
            <qti-inline-choice-interaction response-identifier="RESPONSE" min-choices="1"
                id="ic1" class="fancy" xml:lang="en" dir="ltr"
                data-custom="x" aria-label="pick one" role="listbox"
                not-a-real-attribute="drop-me">
                <qti-inline-choice identifier="A" data-note="keep" bogus="drop">Alpha</qti-inline-choice>
            </qti-inline-choice-interaction>
        ');

        $result = $this->parser->parse($element);

        // Element-specific attributes are read into their own typed properties.
        $this->assertSame(1, $result->minChoices);

        // Shared globals become typed properties; role is typed; aria-*/data-* are maps.
        $this->assertSame('ic1', $result->id);
        $this->assertSame('fancy', $result->class);
        $this->assertSame('en', $result->xmlLang);
        $this->assertSame('ltr', $result->dir);
        $this->assertSame('listbox', $result->role);
        $this->assertSame(['aria-label' => 'pick one'], $result->ariaAttributes);
        $this->assertSame(['data-custom' => 'x'], $result->dataAttributes);

        // The unknown attribute is not permitted for this element and is dropped.
        $this->assertArrayNotHasKey('not-a-real-attribute', $result->dataAttributes);
        $this->assertArrayNotHasKey('not-a-real-attribute', $result->ariaAttributes);

        // The same allowlisting applies to the child element.
        $this->assertSame(['data-note' => 'keep'], $result->choices[0]->dataAttributes);
    }

    #[Test]
    public function dropsAriaAttributesNotEnumeratedByTheSchema(): void
    {
        // aria-label is enumerated by ARIABaseDType; aria-foo is not and must be dropped.
        $element = $this->loadElement('
            <qti-inline-choice-interaction response-identifier="RESPONSE"
                aria-label="keep me" aria-foo="drop me">
                <qti-inline-choice identifier="A">Alpha</qti-inline-choice>
            </qti-inline-choice-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertSame(['aria-label' => 'keep me'], $result->ariaAttributes);
        $this->assertArrayNotHasKey('aria-foo', $result->ariaAttributes);
    }

    #[Test]
    public function dropsAriaAndRoleOnElementsThatDoNotAllowThem(): void
    {
        // qti-simple-match-set only permits id (+ data-*): it does not extend the ARIA base.
        $element = $this->loadElement('
            <qti-match-interaction response-identifier="RESPONSE">
                <qti-simple-match-set id="set1" role="list" aria-label="nope" data-x="y" foo="bar">
                    <qti-simple-associable-choice identifier="S1" match-max="1">Source</qti-simple-associable-choice>
                </qti-simple-match-set>
                <qti-simple-match-set/>
            </qti-match-interaction>
        ');

        $set = $this->parser->parse($element)->simpleMatchSet1;

        $this->assertSame('set1', $set->id);
        $this->assertSame(['data-x' => 'y'], $set->dataAttributes);
        // role/aria-* are not permitted here, nor is the unknown attribute.
        $this->assertNull($set->role);
        $this->assertSame([], $set->ariaAttributes);
        $this->assertArrayNotHasKey('foo', $set->dataAttributes);
    }

    #[Test]
    public function parseMatchInteraction(): void
    {
        $element = $this->loadElement('
            <qti-match-interaction response-identifier="RESPONSE_MATCH" shuffle="true" max-associations="4" class="matrix">
                <qti-simple-match-set>
                    <qti-simple-associable-choice identifier="S1">Source 1</qti-simple-associable-choice>
                    <qti-simple-associable-choice identifier="S2">Source 2</qti-simple-associable-choice>
                </qti-simple-match-set>
                <qti-simple-match-set>
                    <qti-simple-associable-choice identifier="T1">Target 1</qti-simple-associable-choice>
                    <qti-simple-associable-choice identifier="T2">Target 2</qti-simple-associable-choice>
                </qti-simple-match-set>
            </qti-match-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(MatchInteraction::class, $result);
        $this->assertSame('RESPONSE_MATCH', $result->responseIdentifier);
        $this->assertTrue($result->shuffle);
        $this->assertSame(4, $result->maxAssociations);
        $this->assertSame('matrix', $result->class);

        $this->assertCount(2, $result->simpleMatchSet1->choices);
        $this->assertSame('S1', $result->simpleMatchSet1->choices[0]->identifier);
        $this->assertSame('S2', $result->simpleMatchSet1->choices[1]->identifier);

        $this->assertCount(2, $result->simpleMatchSet2->choices);
        $this->assertSame('T1', $result->simpleMatchSet2->choices[0]->identifier);
        $this->assertSame('T2', $result->simpleMatchSet2->choices[1]->identifier);

        $content = $result->simpleMatchSet1->choices[0]->content->all();
        $this->assertInstanceOf(TextNode::class, $content[0]);
        $this->assertSame('Source 1', $content[0]->content);
    }

    #[Test]
    public function parseOrderInteraction(): void
    {
        $element = $this->loadElement('
            <qti-order-interaction response-identifier="RESPONSE_ORDER" shuffle="true" orientation="horizontal">
                <qti-simple-choice identifier="O1">First</qti-simple-choice>
                <qti-simple-choice identifier="O2">Second</qti-simple-choice>
                <qti-simple-choice identifier="O3">Third</qti-simple-choice>
            </qti-order-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(OrderInteraction::class, $result);
        $this->assertSame('RESPONSE_ORDER', $result->responseIdentifier);
        $this->assertTrue($result->shuffle);
        $this->assertSame(Orientation::HORIZONTAL, $result->orientation);
        $this->assertCount(3, $result->choices);
        $this->assertSame('O1', $result->choices[0]->identifier);
        $this->assertSame('O2', $result->choices[1]->identifier);
        $this->assertSame('O3', $result->choices[2]->identifier);
    }

    #[Test]
    public function parseSelectPointInteraction(): void
    {
        $element = $this->loadElement('
            <qti-select-point-interaction response-identifier="RESPONSE_SP" max-choices="3">
                <qti-prompt>Select points on the image</qti-prompt>
                <img src="diagram.png" alt="A diagram"/>
            </qti-select-point-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(SelectPointInteraction::class, $result);
        $this->assertSame('RESPONSE_SP', $result->responseIdentifier);
        $this->assertSame(3, $result->maxChoices);
        $this->assertNotNull($result->prompt);
        $this->assertSame('Select points on the image', $result->prompt->content->all()[0]->content);
        $this->assertSame('img', $result->image->tagName());
    }

    #[Test]
    public function parseSelectPointInteractionWithoutImage(): void
    {
        $element = $this->loadElement('
            <qti-select-point-interaction response-identifier="RESPONSE" max-choices="1">
                <qti-prompt>Click on the image</qti-prompt>
            </qti-select-point-interaction>
        ');

        $this->expectException(ParseError::class);
        $this->expectExceptionMessage('SelectPointInteraction is missing a required <img> or <picture> element.');

        $this->parser->parse($element);
    }

    #[Test]
    public function parseHottextInteractionWithHottext(): void
    {
        $element = $this->loadElement('
            <qti-hottext-interaction response-identifier="RESPONSE_HT" max-choices="1">
                <p>The <qti-hottext identifier="ht1">cat</qti-hottext> sat on the <qti-hottext identifier="ht2">mat</qti-hottext>.</p>
            </qti-hottext-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(HottextInteraction::class, $result);
        $content = $result->content->all();
        $this->assertCount(1, $content);

        $p = $content[0];
        $this->assertInstanceOf(HTMLTag::class, $p);

        $children = $p->children();
        $this->assertInstanceOf(TextNode::class, $children[0]);
        $this->assertSame('The ', $children[0]->content);
        $this->assertInstanceOf(Hottext::class, $children[1]);
        $this->assertSame('ht1', $children[1]->identifier);
        $this->assertSame('cat', $children[1]->content->all()[0]->content);
        $this->assertInstanceOf(TextNode::class, $children[2]);
        $this->assertSame(' sat on the ', $children[2]->content);
        $this->assertInstanceOf(Hottext::class, $children[3]);
        $this->assertSame('ht2', $children[3]->identifier);
        $this->assertSame('mat', $children[3]->content->all()[0]->content);
    }

    #[Test]
    public function parseGapMatchInteractionWithGapAndGapText(): void
    {
        $element = $this->loadElement('
            <qti-gap-match-interaction response-identifier="RESPONSE_GAP" shuffle="false">
                <qti-gap-text identifier="gt1" match-max="1">winter</qti-gap-text>
                <qti-gap-text identifier="gt2" match-max="1">summer</qti-gap-text>
                <p>In <qti-gap identifier="G1"/> the weather is cold.</p>
            </qti-gap-match-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(GapMatchInteraction::class, $result);
        $content = $result->content->all();

        // Find GapText elements
        $gapTexts = array_values(array_filter($content, fn($node) => $node instanceof GapText));
        $this->assertCount(2, $gapTexts);
        $this->assertSame('gt1', $gapTexts[0]->identifier);
        $this->assertSame(1, $gapTexts[0]->matchMax);
        $this->assertSame('winter', $gapTexts[0]->content->all()[0]->content);
        $this->assertSame('gt2', $gapTexts[1]->identifier);

        // Find the paragraph containing the Gap
        $paragraphs = array_values(array_filter($content, fn($node) => $node instanceof HTMLTag));
        $this->assertCount(1, $paragraphs);
        $pChildren = $paragraphs[0]->children();
        $gaps = array_values(array_filter($pChildren, fn($node) => $node instanceof Gap));
        $this->assertCount(1, $gaps);
        $this->assertSame('G1', $gaps[0]->identifier);
    }

    #[Test]
    public function parseChoiceInteractionWithFeedbackInline(): void
    {
        $element = $this->loadElement('
            <qti-choice-interaction response-identifier="RESPONSE" max-choices="1">
                <qti-simple-choice identifier="A">
                    Option A
                    <qti-feedback-inline identifier="fb-a" outcome-identifier="FEEDBACK" show-hide="show">Correct!</qti-feedback-inline>
                </qti-simple-choice>
            </qti-choice-interaction>
        ');

        $result = $this->parser->parse($element);

        $this->assertInstanceOf(ChoiceInteraction::class, $result);
        $this->assertCount(1, $result->choices);
        $choice = $result->choices[0];
        $this->assertSame('A', $choice->identifier);
        $this->assertNotNull($choice->feedbackInline);
        $this->assertInstanceOf(FeedbackInline::class, $choice->feedbackInline);
        $this->assertSame('fb-a', $choice->feedbackInline->identifier);
        $this->assertSame('FEEDBACK', $choice->feedbackInline->outcomeIdentifier);
        $this->assertSame(Visibility::SHOW, $choice->feedbackInline->showHide);
    }

    #[Test]
    public function parseUnsupportedInteractionThrows(): void
    {
        $element = $this->loadElement('<qti-unknown-interaction/>');

        $this->expectException(ParseError::class);
        $this->expectExceptionMessage('Unsupported interaction: qti-unknown-interaction');

        $this->parser->parse($element);
    }
}
