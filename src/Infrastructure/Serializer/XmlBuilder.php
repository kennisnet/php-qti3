<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Serializer;

use App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\IXmlBuilder;
use DOMDocument;

readonly class XmlBuilder implements IXmlBuilder
{
    public function createDomDocument(): DOMDocument
    {
        $xmlDocument = new DOMDocument('1.0', 'UTF-8');
        $xmlDocument->formatOutput = true;

        return $xmlDocument;
    }

    public function generateXmlFromObject(object $object): DOMDocument
    {
        $xmlDocument = $this->createDomDocument();
        $serializer = new RecursiveXMLSerializer($xmlDocument);

        $serializer->serialize($object);

        return $xmlDocument;
    }
}
