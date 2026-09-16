<?php

declare(strict_types=1);

namespace Qti3\Shared\Xml\Builder;

use DOMDocument;

interface IXmlBuilder
{
    public function createDomDocument(): DOMDocument;

    public function generateXmlFromObject(object $object): DOMDocument;
}
