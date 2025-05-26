<?php

declare(strict_types=1);

namespace App\Tests\Unit\SharedKernel\Domain\Qti\AssessmentItem\Service;

use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\OutcomeDeclarationParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ProcessingElementParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\QtiExpressionParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ResponseDeclarationParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser\ResponseProcessingParser;
use App\SharedKernel\Domain\Qti\AssessmentItem\Service\ResponseProcessor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseProcessorTest extends TestCase
{
    #[Test]
    public function processResponsesWithCorrectMatchResponse(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/simple-match-processing.xml',
            [
                'RESPONSE' => ['A1 B1', 'A2 B2'],
            ],
            [
                'completionStatus' => 'unknown',
                'SCORE' => '1',
                'FEEDBACK' => 'correct',
                'PROCESSED' => true,
            ]
        );
    }

    #[Test]
    public function processResponsesWithIncorrectMatchResponse(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/simple-match-processing.xml',
            [
                'RESPONSE' => ['A1 B2', 'A2 B1'],
            ],
            [
                'completionStatus' => 'unknown',
                'SCORE' => '0',
                'FEEDBACK' => 'incorrect',
                'PROCESSED' => true,
            ]
        );
    }

    #[Test]
    public function processResponsesWithCorrectChoiceResponse(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/simple-choice-processing.xml',
            [
                'RESPONSE' => 'CHOICE1',
            ],
            [
                'completionStatus' => 'unknown',
                'SCORE' => 1.0,
                'FEEDBACK' => 'true',
            ]
        );

    }

    #[Test]
    public function processResponsesWithIncorrectChoiceResponse(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/simple-choice-processing.xml',
            [
                'RESPONSE' => 'CHOICE2',
            ],
            [
                'completionStatus' => 'unknown',
                'SCORE' => 0.0,
                'FEEDBACK' => 'true',
            ]
        );
    }

    #[Test]
    public function processResponsesWithCorrectSelectPointResponse(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/select-point-processing.xml',
            [
                'RESPONSE' => ['476 255', '397 433', '143 197'],
            ],
            [
                'completionStatus' => 'unknown',
                'SCORE' => 1.0,
                'FEEDBACK' => 'correct',
            ]
        );
    }

    #[Test]
    public function processResponsesWithIncorrectSelectPointResponse(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/select-point-processing.xml',
            [
                'RESPONSE' => ['176 55', '97 33', '443 597'],
            ],
            [
                'completionStatus' => 'unknown',
                'SCORE' => 0.0,
                'FEEDBACK' => 'incorrect',
            ]
        );
    }

    #[Test]
    public function defaultValueWithoutValueTagThrowsException(): void
    {
        $this->assertExceptionThrown(
            __DIR__ . '/resources/default-value-without-value.xml',
            ['RESPONSE' => 'A'],
            'Expected tag "qti-value'
        );
    }

    #[Test]
    public function defaultValueWithEmptyValueTagThrowsException(): void
    {
        $this->assertExceptionThrown(
            __DIR__ . '/resources/default-value-empty-value.xml',
            ['RESPONSE' => 'A'],
            'Empty default value'
        );
    }

    #[Test]
    public function defaultValueWithWrongTagThrowsException(): void
    {
        $this->assertExceptionThrown(
            __DIR__ . '/resources/default-value-wrong-tag.xml',
            ['RESPONSE' => 'A'],
            'Expected tag "qti-value", got "qti-wrong-tag"'
        );
    }

    #[Test]
    public function elseIfAfterElseThrowsException(): void
    {
        $this->assertExceptionThrown(
            __DIR__ . '/resources/else-if-after-else.xml',
            ['RESPONSE' => 'A'],
            'Unexpected else if'
        );
    }

    #[Test]
    public function wrongProcessingElementThrowsException(): void
    {
        $this->assertExceptionThrown(
            __DIR__ . '/resources/wrong-processing-element.xml',
            ['RESPONSE' => 'A'],
            'Unknown processing element qti-wrong-element'
        );
    }

    #[Test]
    public function wrongExpressionElementThrowsException(): void
    {
        $this->assertExceptionThrown(
            __DIR__ . '/resources/wrong-expression-element.xml',
            ['RESPONSE' => 'A'],
            'Unknown qti expression tag qti-wrong-element'
        );
    }

    #[Test]
    public function emptyCorrectResponseValueThrowsException(): void
    {
        $this->assertExceptionThrown(
            __DIR__ . '/resources/empty-correct-response-value.xml',
            ['RESPONSE' => 'A'],
            'Empty correct response value'
        );
    }

    #[Test]
    public function nonExistingOutcomeIdentifierThrowsException(): void
    {
        $this->assertExceptionThrown(
            __DIR__ . '/resources/non-existing-outcome.xml',
            ['RESPONSE' => ['A1 B1', 'A2 B2']],
            'Outcome declaration with identifier NON-EXISTING not found'
        );
    }

    #[Test]
    public function partialMatchTriggersElseIf(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/with-else-if.xml',
            [
                'RESPONSE' => ['A1 B1'], // Partial answer
            ],
            [
                'completionStatus' => 'unknown',
                'SCORE' => '0.5',
                'FEEDBACK' => 'incorrect',
            ]
        );
    }

    #[Test]
    public function complexResponseProcessingWorks(): void
    {
        // Arrange

        $responseProcessor = $this->getResponseProcessor();
        $itemState = $responseProcessor->initItemState(file_get_contents(__DIR__ . '/resources/complex-response-processing.xml'));

        // Act & Assert

        // Step 1
        $responseProcessor->processResponses($itemState, [
            'RESPONSE1' => 'OPTION2',
            'RESPONSE21' => null,
            'RESPONSE22' => 'OPTION221',
            'RESPONSE23' => null,
            'RESPONSE24' => null,
            'RESPONSE25' => null,
            'RESPONSE26' => null,
            'RESPONSE27' => null,
        ]);
        $this->assertEquals([
            'completionStatus' => 'incomplete',
            'BODY' => ['part2', 'option2'],
        ], $itemState->outcomeSet->outcomes);

        // Step 2
        $responseProcessor->processResponses($itemState, [
            'RESPONSE1' => 'OPTION2',
            'RESPONSE21' => null,
            'RESPONSE22' => 'OPTION221',
            'RESPONSE23' => 'OPTION231',
            'RESPONSE24' => 'OPTION241',
            'RESPONSE25' => null,
            'RESPONSE26' => null,
            'RESPONSE27' => null,
        ]);
        $this->assertEquals([
            'completionStatus' => 'completed',
            'SCORE' => 10,
            'FEEDBACK' => 'CORRECT',
            'BODY' => ['part2', 'option2'],
        ], $itemState->outcomeSet->outcomes);

    }

    #[Test]
    public function complexResponseProcessingWorks2(): void
    {
        // Arrange

        $responseProcessor = $this->getResponseProcessor();
        $itemState = $responseProcessor->initItemState(file_get_contents(__DIR__ . '/resources/complex-response-processing.xml'));

        // Act & Assert

        // Step 1
        $responseProcessor->processResponses($itemState, [
            'RESPONSE1' => 'OPTION2',
            'RESPONSE21' => null,
            'RESPONSE22' => null,
            'RESPONSE23' => null,
            'RESPONSE24' => null,
            'RESPONSE25' => null,
            'RESPONSE26' => null,
            'RESPONSE27' => null,
        ]);
        $this->assertEquals([
            'completionStatus' => 'incomplete',
            'BODY' => ['part2', 'option2'],
        ], $itemState->outcomeSet->outcomes);

        // Step 2
        $responseProcessor->processResponses($itemState, [
            'RESPONSE1' => 'OPTION2',
            'RESPONSE21' => null,
            'RESPONSE22' => 'OPTION221',
            'RESPONSE23' => 'OPTION232',
            'RESPONSE24' => 'OPTION241',
            'RESPONSE25' => null,
            'RESPONSE26' => null,
            'RESPONSE27' => null,
        ]);
        $this->assertEquals([
            'completionStatus' => 'completed',
            'SCORE' => 5,
            'FEEDBACK' => 'PARTIAL',
            'BODY' => ['part2', 'option2'],
        ], $itemState->outcomeSet->outcomes);

    }

    #[Test]
    public function testNumberComparisons1(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/number-comparison-expressions.xml',
            ['RESPONSE' => 1],
            [
                'completionStatus' => 'unknown',
                'SCORE' => 1.0,
            ]
        );
    }

    #[Test]
    public function testVariousExpressions(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/various-expressions.xml',
            ['RESPONSE' =>  ['AA']],
            [
                'completionStatus' => 'unknown',
                'SCORE' => 1.0,
                'FEEDBACK' => 'incorrect',
            ]
        );
    }

    #[Test]
    public function testNumberComparisons2(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/number-comparison-expressions.xml',
            ['RESPONSE' => 0.6],
            [
                'completionStatus' => 'unknown',
                'SCORE' => 0.7,
            ]
        );
    }

    #[Test]
    public function testNumberComparisons3(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/number-comparison-expressions.xml',
            ['RESPONSE' => 0],
            [
                'completionStatus' => 'unknown',
                'SCORE' => 0.0,
            ]
        );
    }

    #[Test]
    public function testNumberComparisons4(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/number-comparison-expressions.xml',
            ['RESPONSE' => 0.2],
            [
                'completionStatus' => 'unknown',
                'SCORE' => 0.3,
            ]
        );
    }

    #[Test]
    public function processResponsesWithoutResponseProcessingWorks(): void
    {
        $this->assertOutcomes(
            __DIR__ . '/resources/without-response-processing.xml',
            ['RESPONSE' => 'answer'],
            [
                'completionStatus' => 'unknown',
            ]
        );
    }

    public function getResponseProcessor(): ResponseProcessor
    {
        $responseDeclarationParser = new ResponseDeclarationParser();
        $outcomeDeclarationParser = new OutcomeDeclarationParser();
        $responseProcessingParser = new ResponseProcessingParser(
            new ProcessingElementParser(
                new QtiExpressionParser()
            )
        );

        $responseProcessor = new ResponseProcessor(
            $responseDeclarationParser,
            $outcomeDeclarationParser,
            $responseProcessingParser
        );
        return $responseProcessor;
    }

    private function assertOutcomes(string $itemXmlFile, array $responses, array $expectedOutcomes): void
    {
        // Arrange
        $responseProcessor = $this->getResponseProcessor();
        $itemState = $responseProcessor->initItemState(file_get_contents($itemXmlFile));

        // Act
        $responseProcessor->processResponses($itemState, $responses);

        // Assert
        $this->assertEquals($expectedOutcomes, $itemState->outcomeSet->outcomes);
    }

    private function assertExceptionThrown(string $itemXmlFile, array $responses, string $expectedExceptionMessage): void
    {
        // Arrange
        $responseProcessor = $this->getResponseProcessor();

        // Act & Assert
        $this->expectExceptionMessage($expectedExceptionMessage);

        $itemState = $responseProcessor->initItemState(file_get_contents($itemXmlFile));
        $responseProcessor->processResponses($itemState, $responses);
    }
}
