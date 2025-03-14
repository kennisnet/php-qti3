<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Service\QtiPackageBuilder\Manifest;

use DOMDocument;
use DOMElement;

class OrganizationsBuilder
{
    public function addOrganizationsNode(DOMDocument $document, DOMElement $rootNode): void
    {
        $organizationsNode = $document->createElement('organizations');
        $rootNode->appendChild($organizationsNode);
    }
}
