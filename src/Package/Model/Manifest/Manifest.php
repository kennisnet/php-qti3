<?php

declare(strict_types=1);

namespace Qti3\Package\Model\Manifest;

use Qti3\Package\Model\FileContent\IFileContent;
use Qti3\Package\Model\FileContent\MemoryFileContent;
use Qti3\Package\Model\PackageFile\XmlFile;
use Qti3\Package\Model\Resource\ResourceType;
use Qti3\Shared\Xml\Reader\IXmlReader;
use DOMElement;
use DOMXPath;
use RuntimeException;

class Manifest extends XmlFile
{
    public const string MANIFEST_FILE_NAME = 'imsmanifest.xml';
    private readonly DOMXPath $xpath;

    private function __construct(
        IFileContent $fileContent,
        private readonly IXmlReader $xmlReader,
    ) {
        parent::__construct(self::MANIFEST_FILE_NAME, $fileContent, $this->xmlReader);

        $this->xpath = new DOMXPath($this->getXml());
        $this->xpath->registerNamespace('lom', 'http://ltsc.ieee.org/xsd/LOM');
    }

    public static function fromString(string $content, IXmlReader $xmlReader): self
    {
        return new self(
            new MemoryFileContent($content),
            $xmlReader,
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
        $resourceNode = $this->createManifestElement('resource');
        $resourceNode->setAttribute('identifier', $resource->identifier);
        $resourceNode->setAttribute('type', $resource->type->value);
        if ($resource->href !== null) {
            $resourceNode->setAttribute('href', $resource->href);
        }

        foreach ($resource->files as $file) {
            $fileNode = $this->createManifestElement('file');
            $fileNode->setAttribute('href', $file->href);
            $resourceNode->appendChild($fileNode);
        }

        foreach ($resource->dependencies as $dependency) {
            $dependencyNode = $this->createManifestElement('dependency');
            $dependencyNode->setAttribute('identifierref', $dependency->identifierref);
            $resourceNode->appendChild($dependencyNode);
        }

        $this->getResourcesElement()->appendChild($resourceNode);
    }

    /** Append a `<dependency>` to an existing resource node. */
    public function addDependency(string $resourceIdentifier, string $dependencyRef): void
    {
        $dependencyNode = $this->createManifestElement('dependency');
        $dependencyNode->setAttribute('identifierref', $dependencyRef);

        $this->findResourceNode($resourceIdentifier)->appendChild($dependencyNode);
    }

    private function findResourceNode(string $identifier): DOMElement
    {
        foreach ($this->getXml()->getElementsByTagName('resource') as $resourceNode) {
            if ($resourceNode instanceof DOMElement && $resourceNode->getAttribute('identifier') === $identifier) {
                return $resourceNode;
            }
        }

        throw new RuntimeException(sprintf('Manifest has no resource with identifier %s', $identifier));
    }

    /**
     * New elements must live in the manifest's own namespace, or namespaced
     * consumers (validators, other QTI tooling) will not see them.
     */
    private function createManifestElement(string $name): DOMElement
    {
        $namespace = $this->getDocumentElement()->namespaceURI;
        $element = $namespace === null
            ? $this->getXml()->createElement($name)
            : $this->getXml()->createElementNS($namespace, $name);

        if (!$element instanceof DOMElement) {
            throw new RuntimeException(sprintf('Failed to create manifest element <%s>', $name)); // @codeCoverageIgnore
        }

        return $element;
    }

    private function getResourcesElement(): DOMElement
    {
        foreach ($this->getDocumentElement()->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && $childNode->localName === 'resources') {
                return $childNode;
            }
        }

        $resources = $this->createManifestElement('resources');
        $this->getDocumentElement()->appendChild($resources);

        return $resources;
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
}
