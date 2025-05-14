<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\AssessmentItem\Service\Parser;

use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\AreaMapEntry;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\AreaMapping;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\CorrectResponse;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\MapEntry;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\Mapping;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\ResponseDeclaration\ResponseDeclaration;
use App\SharedKernel\Domain\Qti\AssessmentItem\Model\Shape\ShapeFactory;
use App\SharedKernel\Domain\Qti\Shared\Model\BaseType;
use App\SharedKernel\Domain\Qti\Shared\Model\Cardinality;
use App\SharedKernel\Domain\Qti\Shared\Model\Value;
use DOMElement;

class ResponseDeclarationParser extends AbstractParser
{
    public function parse(DOMElement $element): ResponseDeclaration
    {
        $this->validateTag($element, ResponseDeclaration::qtiTagName());

        return new ResponseDeclaration(
            BaseType::from($element->getAttribute('base-type')),
            Cardinality::from($element->getAttribute('cardinality')),
            $element->getAttribute('identifier'),
            $this->parseCorrectResponse($element),
            $this->parseMapping($element),
            $this->parseAreaMapping($element),
        );
    }

    private function parseCorrectResponse(DOMElement $element): ?CorrectResponse
    {
        $correctResponse = array_find(
            $this->getChildren($element),
            fn($child): bool => $child->nodeName === CorrectResponse::qtiTagName()
        );
        if (!$correctResponse) {
            return null;
        }
        $correctResponseChildren = $this->getChildren($correctResponse);
        return new CorrectResponse(array_map(
            function($correctResponseChild): Value {
                $this->validateTag($correctResponseChild, Value::qtiTagName());
                $value = $correctResponseChild->nodeValue;
                if ($value === null || $value === '') {
                    throw new ParseError('Empty correct response value');
                }
                return new Value($value);
            },
            $correctResponseChildren
        ));
    }

    private function parseMapping(DOMElement $element): ?Mapping
    {
        $mapping = array_find(
            $this->getChildren($element),
            fn($child): bool => $child->nodeName === Mapping::qtiTagName()
        );
        if (!$mapping) {
            return null;
        }
        $mappingChildren = $this->getChildren($mapping);
        return new Mapping(
            array_map(
                function($mappingChild): MapEntry {
                    $this->validateTag($mappingChild, MapEntry::qtiTagName());
                    return new MapEntry(
                        $mappingChild->getAttribute('map-key'),
                        (float) $mappingChild->getAttribute('mapped-value'),
                        $mappingChild->getAttribute('case-sensitive') === 'true'
                    );
                },
                $mappingChildren
            ),
        );
    }

    private function parseAreaMapping(DOMElement $element): ?AreaMapping
    {
        $areaMapping = array_find(
            $this->getChildren($element),
            fn($child): bool => $child->nodeName === AreaMapping::qtiTagName()
        );
        if (!$areaMapping) {
            return null;
        }
        $areaMappingChildren = $this->getChildren($areaMapping);
        return new AreaMapping(
            array_map(
                function($areaMappingChild): AreaMapEntry {
                    $this->validateTag($areaMappingChild, AreaMapEntry::qtiTagName());
                    return new AreaMapEntry(
                        ShapeFactory::create(
                            $areaMappingChild->getAttribute('shape'),
                            $areaMappingChild->getAttribute('coords')
                        ),
                        (float) $areaMappingChild->getAttribute('mapped-value'),
                    );
                },
                $areaMappingChildren
            ),
        );
    }
}
