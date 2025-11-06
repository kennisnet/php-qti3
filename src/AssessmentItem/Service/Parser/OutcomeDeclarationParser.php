<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser;

use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\DefaultValue;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\ExternalScored;
use App\SharedKernel\Domain\Qti\Shared\Model\OutcomeDeclaration\OutcomeDeclaration;
use App\SharedKernel\Domain\Qti\Shared\Model\Value;
use DOMElement;

class OutcomeDeclarationParser extends AbstractParser
{
    public function parse(DOMElement $element): OutcomeDeclaration
    {
        $this->validateTag($element, OutcomeDeclaration::qtiTagName());

        return new OutcomeDeclaration(
            $element->getAttribute('identifier'),
            BaseType::from($element->getAttribute('base-type')),
            Cardinality::from($element->getAttribute('cardinality')),
            $this->parseDefaultValue($element),
            $this->parseFloat($element->getAttribute('normal-maximum')),
            $this->parseFloat($element->getAttribute('normal-minimum')),
            ExternalScored::tryFrom($element->getAttribute('external-scored')),
        );
    }

    private function parseDefaultValue(DOMElement $element): ?DefaultValue
    {
        $defaultValue = array_find(
            $this->getChildren($element),
            fn($child): bool => $child->nodeName === DefaultValue::qtiTagName(),
        );
        if (!$defaultValue) {
            return null;
        }

        $defaultValueChildren = $this->getChildren($defaultValue);
        $this->validateTag($defaultValueChildren[0] ?? null, Value::qtiTagName());
        $value = $defaultValueChildren[0]->nodeValue;

        if ($value === null || $value === '') {
            throw new ParseError('Empty default value for `qti-outcome-declaration`'); // @codeCoverageIgnore
        }

        return new DefaultValue(new Value($value));
    }

}
