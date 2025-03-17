<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Qti\Package\Model\Manifest;

use App\SharedKernel\Domain\Qti\Package\Model\FileContent\XmlFileContent;
use App\SharedKernel\Domain\Qti\Package\Model\PackageFile\PackageFile;
use App\SharedKernel\Domain\Qti\Package\Model\ResourceFile\ResourceType;
use App\SharedKernel\Domain\Qti\Shared\Xml\Reader\IXmlReader;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

class Manifest extends PackageFile
{
    public const string MANIFEST_FILE_NAME = 'imsmanifest.xml';
    private readonly DOMXPath $xpath;

    private function __construct(
        XmlFileContent $fileContent,
    ) {
        parent::__construct(self::MANIFEST_FILE_NAME, $fileContent);

        $this->xpath = new DOMXPath($this->getXml());
        $this->xpath->registerNamespace('lom', 'http://ltsc.ieee.org/xsd/LOM');
    }

    public static function fromDomDocument(DOMDocument $document): self
    {
        return new self(new XmlFileContent($document));
    }

    public static function fromString(string $content, IXmlReader $xmlReader): self
    {
        return new self(
            XmlFileContent::fromString($content, $xmlReader),
        );
    }

    public function getIdentifier(): string
    {
        return $this->getDocumentElement()->getAttribute('identifier');
    }

    public function getResources(): ManifestResourceCollection
    {
        $resourceNodes = $this->getXml()->getElementsByTagName('resource');

        $resources = new ManifestResourceCollection();
        foreach ($resourceNodes as $resourceNode) {
            $resources->add(new ManifestResource(
                $resourceNode->getAttribute('identifier'),
                ResourceType::tryFrom($resourceNode->getAttribute('type')) ?? throw new RuntimeException(sprintf('Invalid resource type: %s', $resourceNode->getAttribute('type'))),
                $this->getFiles($resourceNode),
                $this->getDependencies($resourceNode),
                $resourceNode->getAttribute('href'),
            ));
        }
        return $resources;
    }

    public function addResource(ManifestResource $resource): void
    {
        /** @var DOMElement $resourceNode */
        $resourceNode = $this->getXml()->createElement('resource');
        $resourceNode->setAttribute('identifier', $resource->identifier);
        $resourceNode->setAttribute('type', $resource->type->value);
        if ($resource->href !== null) {
            $resourceNode->setAttribute('href', $resource->href);
        }

        foreach ($resource->files as $file) {
            $fileNode = $this->getXml()->createElement('file');
            $fileNode->setAttribute('identifierref', $file->href);
            $resourceNode->appendChild($fileNode);
        }

        foreach ($resource->dependencies as $dependency) {
            $dependencyNode = $this->getXml()->createElement('dependency');
            $dependencyNode->setAttribute('identifierref', $dependency->identifierref);
            $resourceNode->appendChild($dependencyNode);
        }

        $this->getDocumentElement()->appendChild($resourceNode);
    }

    private function getFiles(DOMElement $resourceNode): ManifestResourceFileCollection
    {
        $fileNodes = $resourceNode->getElementsByTagName('file');

        $files = new ManifestResourceFileCollection();
        foreach ($fileNodes as $fileNode) {
            $files->add(new ManifestResourceFile(
                $fileNode->getAttribute('href'),
            ));
        }
        return $files;
    }

    private function getDependencies(DOMElement $resourceNode): ManifestResourceDependencyCollection
    {
        $dependencyNodes = $resourceNode->getElementsByTagName('dependency');

        $dependencies = new ManifestResourceDependencyCollection();

        foreach ($dependencyNodes as $dependencyNode) {
            $dependencies->add(new ManifestResourceDependency($dependencyNode->getAttribute('identifierref')));
        }
        return $dependencies;
    }

    private function getXml(): DOMDocument
    {
        if (!$this->content instanceof XmlFileContent) {
            throw new RuntimeException('Invalid content type'); // @codeCoverageIgnore
        }
        return $this->content->xmlDocument;
    }

    private function getDocumentElement(): DOMElement
    {
        $documentElement = $this->getXml()->documentElement;
        if (!$documentElement) {
            throw new RuntimeException('Invalid XML document'); // @codeCoverageIgnore
        }

        return $documentElement;
    }
}
