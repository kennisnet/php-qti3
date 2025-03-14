<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\Shared\Model;

use App\SharedKernel\Domain\Qti\Shared\Model\ContentBody;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\TextNode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContentBodyTest extends TestCase
{
    private ContentBody $contentBody;
    private TextNode $textNode;

    protected function setUp(): void
    {
        $this->textNode = new TextNode('test');
        $this->contentBody = new ContentBody(new ContentNodeCollection([$this->textNode]));
    }

    #[Test]
    public function constructorInitializesValueCorrectly(): void
    {
        $this->assertInstanceOf(TextNode::class, $this->contentBody->children()[0]);
        $this->assertEquals('test', (string) $this->contentBody->children()[0]);
    }
}
