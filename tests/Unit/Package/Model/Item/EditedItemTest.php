<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Model\Item;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Qti3\Package\Model\Item\EditedItem;

final class EditedItemTest extends TestCase
{
    #[Test]
    public function itExposesTheIdentifierAndXml(): void
    {
        $editedItem = new EditedItem('ITEM001', '<qti-assessment-item/>');

        $this->assertSame('ITEM001', $editedItem->identifier);
        $this->assertSame('<qti-assessment-item/>', $editedItem->xml);
    }
}
