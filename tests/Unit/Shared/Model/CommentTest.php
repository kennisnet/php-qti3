<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Shared\Model;

use DOMDocument;
use Qti3\Shared\Model\Comment;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    #[Test]
    public function aCommentCanBeCreatedWithContent(): void
    {
        // A comment holds the text between the delimiters, not the delimiters.
        $comment = new Comment('This is a comment');

        $this->assertEquals('This is a comment', $comment->getContentForXml());
    }

    #[Test]
    public function aDoubleHyphenIsSeparatedSoTheCommentStaysWellFormed(): void
    {
        $comment = new Comment(' TODO: fix -- see ticket ');

        $this->assertEquals(' TODO: fix - - see ticket ', $comment->getContentForXml());
    }

    #[Test]
    public function aTrailingHyphenIsSeparatedFromTheClosingDelimiter(): void
    {
        $comment = new Comment('ends with -');

        $this->assertEquals('ends with - ', $comment->getContentForXml());
    }

    #[Test]
    public function aSanitizedCommentCanBeWrittenAndParsedBack(): void
    {
        $document = new DOMDocument();
        $root = $document->appendChild($document->createElement('root'));
        $root->appendChild($document->createComment((new Comment('a -- b ---'))->getContentForXml()));

        $reparsed = new DOMDocument();

        $this->assertTrue($reparsed->loadXML((string) $document->saveXML()));
    }
}
