<?php

declare(strict_types=1);

namespace Qti3\Shared\Model;

class Comment extends TextNode
{
    /**
     * XML forbids `--` in a comment and a trailing `-`, and `createComment()`
     * escapes neither, so the hyphens are spaced out to keep the XML parseable.
     */
    public function getContentForXml(): string
    {
        $content = preg_replace('/-(?=-)/', '- ', $this->content) ?? $this->content;

        return str_ends_with($content, '-') ? $content . ' ' : $content;
    }
}
