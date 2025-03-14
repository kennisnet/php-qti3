<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Model;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItem;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\AssessmentItemId;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ItemBody;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclarationCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\ContentNodeCollection;
use App\SharedKernel\Domain\Qti\Shared\Model\HTMLTag;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;

class AssessmentItemStub
{
    public static function assessmentItem(): AssessmentItem
    {
        $itemBody = new ItemBody(new ContentNodeCollection([
            new HtmlTag('p'),
        ]));

        return new AssessmentItem(
            identifier: AssessmentItemId::fromQuestionnaire(1, 0),
            itemBody: $itemBody,
            responseDeclarations: new ResponseDeclarationCollection(),
            outcomeDeclarations: new OutcomeDeclarationCollection(),
        );
    }

    public static function assessmentItemWithImage(): AssessmentItem
    {
        $itemBody = new ItemBody(new ContentNodeCollection([
            new HtmlTag('p'),
        ]));

        return new AssessmentItem(
            identifier: AssessmentItemId::fromQuestionnaire(1, 0),
            itemBody: $itemBody,
            responseDeclarations: new ResponseDeclarationCollection(),
            outcomeDeclarations: new OutcomeDeclarationCollection(),
        );
    }
}
