<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model;

use App\SharedKernel\Domain\Qti\Shared\Model\Comment;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    private Comment $comment;

    protected function setUp(): void
    {
        $content = '<!--This is a comment-->';
        $this->comment = new Comment($content);
    }

    #[Test]
    public function aCommentCanBeCreatedWithContent(): void
    {
        $this->assertEquals($this->comment->getContentForXml(), '<!--This is a comment-->');
    }
}
