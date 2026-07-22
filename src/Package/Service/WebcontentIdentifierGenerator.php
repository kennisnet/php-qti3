<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\WebcontentCollection;

final class WebcontentIdentifierGenerator
{
    private const string FORMAT = 'RESOURCE%03d';

    public function next(?QtiPackage $package = null, WebcontentCollection $pending = new WebcontentCollection()): string
    {
        $used = [];
        foreach ($pending as $webcontent) {
            $used[$webcontent->identifier] = true;
        }
        if ($package !== null) {
            foreach ($package->resources as $resource) {
                $used[$resource->identifier] = true;
            }
        }

        $index = 1;
        while (isset($used[$identifier = sprintf(self::FORMAT, $index)])) {
            $index++;
        }

        return $identifier;
    }
}
