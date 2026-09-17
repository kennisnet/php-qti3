<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service\Parser;

use DOMElement;
use Qti3\AssessmentItem\Model\Feedback\Visibility;
use Qti3\AssessmentItem\Service\Parser\AbstractParser;
use Qti3\AssessmentTest\Model\Feedback\TestFeedback;
use Qti3\AssessmentTest\Model\Feedback\TestFeedbackAccess;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Html\ContentNodeParser;
use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Model\ContentNodeCollection;

/** Parses a test-level `<qti-test-feedback>`, content included, so it survives regeneration. */
class TestFeedbackParser extends AbstractParser
{
    public function parse(DOMElement $element, ?StringCollection $warnings = null): TestFeedback
    {
        $this->validateTag($element, TestFeedback::qtiTagName());

        $this->warnUnconsumed(
            $element,
            ['identifier', 'outcome-identifier', 'show-hide', 'access', 'title'],
            ['qti-content-body', ...ContentBody::ALLOWED_HTML_TAGS],
            $warnings ?? new StringCollection(),
        );

        $contentRoot = $this->unwrapContentBody($element);

        $content = new ContentNodeCollection();
        foreach ($contentRoot->childNodes as $child) {
            $node = ContentNodeParser::parse($child);
            if ($node === null) {
                continue;
            }

            $this->warnUnlistedDirectChild($child, $node, ContentBody::allowsAsDirectChild(...), $warnings);
            $content->add($node);
        }

        return new TestFeedback(
            $element->getAttribute('identifier'),
            $element->getAttribute('outcome-identifier'),
            new ContentBody($content),
            $this->parseAccess($element, $warnings),
            $this->parseShowHide($element, $warnings),
            $element->getAttribute('title') ?: null,
        );
    }

    private function parseAccess(DOMElement $element, ?StringCollection $warnings): TestFeedbackAccess
    {
        $raw = $element->getAttribute('access');
        $access = TestFeedbackAccess::tryFrom($raw);
        if ($access === null && $raw !== '') {
            $warnings?->add(sprintf('%s: defaults unknown access "%s" to "%s"', $this->locate($element), $raw, TestFeedbackAccess::AT_END->value));
        }

        return $access ?? TestFeedbackAccess::AT_END;
    }

    private function parseShowHide(DOMElement $element, ?StringCollection $warnings): Visibility
    {
        $raw = $element->getAttribute('show-hide');
        $visibility = Visibility::tryFrom($raw);
        if ($visibility === null && $raw !== '') {
            $warnings?->add(sprintf('%s: defaults unknown show-hide "%s" to "%s"', $this->locate($element), $raw, Visibility::SHOW->value));
        }

        return $visibility ?? Visibility::SHOW;
    }
}
