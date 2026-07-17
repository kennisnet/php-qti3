<?php

declare(strict_types=1);

namespace Qti3\Package\Validator;

use Qti3\Package\Exception\InvalidAssessmentItemException;

/**
 * Validates a single QTI 3 assessment item XML string.
 *
 * Implementations may differ in strictness: {@see AssessmentItemValidator}
 * does fast structural checks for interactive editing; an XSD-based
 * implementation (reusing {@see QtiSchemaValidator}'s schemas) can implement
 * this interface for full schema conformance at publish time.
 */
interface IAssessmentItemValidator
{
    /**
     * @throws InvalidAssessmentItemException when the item XML is not valid
     */
    public function validate(string $itemXml): void;
}
