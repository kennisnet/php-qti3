<?php

declare(strict_types=1);

namespace Qti3\Package\Service;

use Qti3\Package\IQtiPackageFactory;
use Qti3\Package\Model\IItemEditor;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Model\Resource\Resource;
use Qti3\Package\Validator\IAssessmentItemValidator;
use Qti3\Shared\Xml\Reader\IXmlReader;

/**
 * Application service for editing assessment items in an extracted package
 * folder. It only orchestrates: every edit loads the package into the domain
 * model ({@see QtiPackage}), applies the change through the model — which
 * keeps manifest and assessment test consistent — and stores the package
 * again through the package writer. All business rules live in the model.
 */
final readonly class PackageItemEditor implements IItemEditor
{
    public function __construct(
        private string $folder,
        private IQtiPackageFactory $packageFactory,
        private IFilesystemPackageFactory $filesystemPackageFactory,
        private IAssessmentItemValidator $itemValidator,
        private ItemIdentifierGenerator $identifierGenerator,
        private IXmlReader $xmlReader,
    ) {}

    public function addItem(string $itemXml): Resource
    {
        $this->itemValidator->validate($itemXml);

        $package = $this->loadPackage();
        $identifier = $this->identifierGenerator->nextIdentifier($package->getItemIdentifiers());
        $item = Resource::assessmentItem($identifier, $itemXml, $this->xmlReader);

        $package->addItem($item);
        $this->storePackage($package);

        return $item;
    }

    public function updateItem(string $identifier, string $itemXml): Resource
    {
        $package = $this->loadPackage();
        // Resolve the item before validating, so a missing item is reported as
        // ResourceNotFoundException even when the new XML is also invalid.
        $package->getItemResource($identifier);
        $this->itemValidator->validate($itemXml);

        $item = $package->updateItem($identifier, $itemXml);
        $this->storePackage($package);

        return $item;
    }

    public function reorderItems(array $orderedIdentifiers): void
    {
        $package = $this->loadPackage();

        $package->reorderItems($orderedIdentifiers);
        $this->storePackage($package);
    }

    private function loadPackage(): QtiPackage
    {
        return $this->packageFactory->fromFilesystem($this->folder);
    }

    private function storePackage(QtiPackage $package): void
    {
        $this->filesystemPackageFactory->getWriter($this->folder)->write($package);
    }
}
