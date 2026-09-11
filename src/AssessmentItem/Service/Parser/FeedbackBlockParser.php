<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service\Parser;

use DOMElement;
use Qti3\AssessmentItem\Model\Feedback\FeedbackBlock;
use Qti3\AssessmentItem\Model\Feedback\Visibility;
use Qti3\Shared\Html\ContentNodeParser;
use Qti3\Shared\Model\ContentBody;
use Qti3\Shared\Model\ContentNodeCollection;
use Qti3\Shared\Model\IXmlElement;

class FeedbackBlockParser extends AbstractParser
{
    public function __construct(private readonly ContentNodeParser $contentNodeParser) {}

    public function parse(DOMElement $element): IXmlElement
    {
        $this->validateTag($element, FeedbackBlock::qtiTagName());

        $identifier = $element->getAttribute('identifier');
        $outcomeIdentifier = $element->getAttribute('outcome-identifier') ?: 'FEEDBACK';
        $showHide = $element->getAttribute('show-hide') ?: Visibility::SHOW->value;
        $visibility = Visibility::from($showHide);

        $contentRoot = $this->unwrapContentBody($element);

        $content = new ContentNodeCollection();
        foreach ($contentRoot->childNodes as $child) {
            $node = $this->contentNodeParser->parse($child);
            if ($node !== null) {
                $content->add($node);
            }
        }

        return new FeedbackBlock($identifier, new ContentBody($content), $outcomeIdentifier, $visibility);
    }
}
