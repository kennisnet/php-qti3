<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\AssessmentItem\Service;

use Qti3\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use Qti3\AssessmentItem\Model\ResponseDeclaration\ResponseDeclarationCollection;
use Qti3\AssessmentItem\Model\ResponseProcessing\ResponseProcessing;
use Qti3\AssessmentItem\Model\State\ItemState;
use Qti3\AssessmentItem\Model\State\OutcomeSet;
use Qti3\AssessmentItem\Model\State\ResponseSet;
use Qti3\AssessmentItem\Service\AssessmentItemDeterminator;
use Qti3\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use Qti3\AssessmentItem\Service\Parser\ResponseDeclarationParser;
use Qti3\AssessmentItem\Service\ScoringOutcomeValidator;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
use Qti3\Shared\Model\OutcomeDeclaration\OutcomeDeclarationCollection;
use DOMDocument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the validator in isolation: the item state is built from the
 * declarations only, with empty response processing. The merge with the
 * processing's own validation is covered by ResponseProcessorTest.
 */
class ScoringOutcomeValidatorTest extends TestCase
{
    private const string RESOURCES = __DIR__ . '/resources/';
    private const string MISSING_SCORE_DECLARATION = 'Missing `qti-outcome-declaration` with identifier `SCORE`';

    private ScoringOutcomeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ScoringOutcomeValidator(new AssessmentItemDeterminator());
    }

    #[Test]
    public function emptyResponseProcessingWithoutScoreDeclarationIsRejected(): void
    {
        $this->assertScoringErrors(
            file_get_contents(self::RESOURCES . 'missing-score-declaration-empty-processing.xml'),
            [self::MISSING_SCORE_DECLARATION],
        );
    }

    #[Test]
    public function unknownTemplateWithoutScoreDeclarationIsRejected(): void
    {
        $this->assertScoringErrors(
            file_get_contents(self::RESOURCES . 'missing-score-declaration-unknown-template.xml'),
            [self::MISSING_SCORE_DECLARATION],
        );
    }

    #[Test]
    public function knownTemplateWithoutScoreDeclarationIsRejected(): void
    {
        $this->assertScoringErrors(
            file_get_contents(self::RESOURCES . 'missing-score-declaration-match-correct.xml'),
            [self::MISSING_SCORE_DECLARATION],
        );
    }

    #[Test]
    public function templateWithScoreDeclarationIsAccepted(): void
    {
        $this->assertNoScoringErrors(file_get_contents(self::RESOURCES . 'match-correct-template.xml'));
    }

    #[Test]
    public function questionWithoutResponseProcessingNeedsNoScoreDeclaration(): void
    {
        // An unscored question (e.g. a questionnaire item) has no response
        // processing element at all and therefore nothing for SCORE to land in.
        $xml = preg_replace(
            '~<qti-response-processing.*?</qti-response-processing>~s',
            '',
            file_get_contents(self::RESOURCES . 'missing-score-declaration-match-correct.xml'),
        );

        $this->assertNoScoringErrors($xml);
    }

    #[Test]
    public function infoItemWithoutInteractionIsNotValidated(): void
    {
        $xml = preg_replace(
            '~<qti-item-body>.*?</qti-item-body>~s',
            '<qti-item-body><p>Read this.</p></qti-item-body>',
            file_get_contents(self::RESOURCES . 'missing-score-declaration-empty-processing.xml'),
        );

        $this->assertNoScoringErrors($xml);
    }

    #[Test]
    public function allViolationsOfAnItemAreReportedTogether(): void
    {
        // Strip the SCORE declaration from an item whose MAXSCORE already lacks a default.
        $xml = preg_replace(
            '~<qti-outcome-declaration identifier="SCORE".*?</qti-outcome-declaration>~s',
            '',
            file_get_contents(self::RESOURCES . 'outcome-declaration-without-default.xml'),
        );

        $this->assertScoringErrors($xml, [
            self::MISSING_SCORE_DECLARATION,
            'Missing default value for MAXSCORE outcome declaration',
        ]);
    }

    #[Test]
    public function missingMaxScoreDeclarationIsCollectedWithTheOtherViolations(): void
    {
        // Without a MAXSCORE declaration at all, the lookup used to throw before
        // the collected SCORE violation could be reported.
        $xml = preg_replace(
            '~<qti-outcome-declaration identifier="MAXSCORE".*?</qti-outcome-declaration>~s',
            '',
            file_get_contents(self::RESOURCES . 'missing-score-declaration-empty-processing.xml'),
        );

        $this->assertScoringErrors($xml, [
            self::MISSING_SCORE_DECLARATION,
            'Outcome declaration with identifier MAXSCORE not found',
        ]);
    }

    #[Test]
    public function existingScoreAndMaxScoreChecksStillApply(): void
    {
        $this->assertScoringErrors(
            file_get_contents(self::RESOURCES . 'outcome-declaration-without-default.xml'),
            ['Missing default value for MAXSCORE outcome declaration'],
        );
    }

    /**
     * @param list<string> $expectedErrors
     */
    private function assertScoringErrors(string $itemXml, array $expectedErrors): void
    {
        [$document, $itemState] = $this->buildItemState($itemXml);

        $this->assertSame($expectedErrors, $this->validator->validate($document, $itemState)->all());
    }

    private function assertNoScoringErrors(string $itemXml): void
    {
        [$document, $itemState] = $this->buildItemState($itemXml);

        $this->assertTrue($this->validator->validate($document, $itemState)->isEmpty());
    }

    /**
     * @return array{0: DOMDocument, 1: ItemState}
     */
    private function buildItemState(string $itemXml): array
    {
        $document = new DOMDocument();
        $document->loadXML($itemXml);

        $responseDeclarations = new ResponseDeclarationCollection();
        $responseDeclarationParser = new ResponseDeclarationParser();
        foreach ($document->getElementsByTagName(ResponseDeclaration::qtiTagName()) as $tag) {
            $responseDeclarations->add($responseDeclarationParser->parse($tag));
        }

        $outcomeDeclarations = new OutcomeDeclarationCollection();
        $outcomeDeclarationParser = new OutcomeDeclarationParser();
        foreach ($document->getElementsByTagName(OutcomeDeclaration::qtiTagName()) as $tag) {
            $outcomeDeclarations->add($outcomeDeclarationParser->parse($tag));
        }

        $itemState = new ItemState(
            new ResponseSet($responseDeclarations),
            new OutcomeSet($outcomeDeclarations),
            new ResponseProcessing([]),
        );

        return [$document, $itemState];
    }
}
