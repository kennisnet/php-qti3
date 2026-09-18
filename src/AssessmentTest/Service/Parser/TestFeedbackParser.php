<?php

declare(strict_types=1);

namespace Qti3\AssessmentTest\Service\Parser;

use DOMElement;
use InvalidArgumentException;
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
    private const string CONTENT_BODY = 'qti-content-body';

    public function parse(DOMElement $element, ?StringCollection $warnings = null): TestFeedback
    {
        $this->validateTag($element, TestFeedback::qtiTagName());
        $warnings ??= new StringCollection();

        $this->warnUnconsumedAttributes($element, ['identifier', 'outcome-identifier', 'show-hide', 'access', 'title'], $warnings);

        // The schema wraps the content in <qti-content-body>; authored XML often leaves the wrapper out.
        // With a wrapper, anything beside it (qti-stylesheet, qti-catalog-info) is dropped; the wrapper's
        // own attributes have no place in the model either. Without one, every child is content.
        $contentRoot = $this->unwrapContentBody($element);
        if ($contentRoot !== $element) {
            $this->warnUnconsumedChildren($element, [self::CONTENT_BODY], $warnings);
            $this->warnUnconsumedAttributes($contentRoot, [], $warnings);
        }

        $content = new ContentNodeCollection();
        foreach ($contentRoot->childNodes as $child) {
            try {
                $node = ContentNodeParser::parse($child);
            } catch (InvalidArgumentException $exception) {
                // Not content the model can hold: a qti-stylesheet where no wrapper separates it from the
                // content, a tag outside the whitelist, or a supported tag carrying an attribute that is
                // not. The whole child goes, since its tree is what failed to build, and the reason says
                // which of the three it was.
                $warnings->add(sprintf('%s: drops <%s>, %s', $this->locate($child), $child->nodeName, $exception->getMessage()));
                continue;
            }
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

    private function parseAccess(DOMElement $element, StringCollection $warnings): TestFeedbackAccess
    {
        $raw = $element->getAttribute('access');
        $access = TestFeedbackAccess::tryFrom($raw);
        if ($access === null && $raw !== '') {
            $warnings->add(sprintf('%s: defaults unknown access "%s" to "%s"', $this->locate($element), $raw, TestFeedbackAccess::AT_END->value));
        }

        return $access ?? TestFeedbackAccess::AT_END;
    }

    private function parseShowHide(DOMElement $element, StringCollection $warnings): Visibility
    {
        $raw = $element->getAttribute('show-hide');
        $visibility = Visibility::tryFrom($raw);
        if ($visibility === null && $raw !== '') {
            $warnings->add(sprintf('%s: defaults unknown show-hide "%s" to "%s"', $this->locate($element), $raw, Visibility::SHOW->value));
        }

        return $visibility ?? Visibility::SHOW;
    }
}
