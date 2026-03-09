<?php

declare(strict_types=1);

namespace Qti3\Package\Filesystem\Zip;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Qti3\Package\Filesystem\FileSystemUtils;
use RuntimeException;
use ZipArchive;

readonly class QtiPackageVersionUpdater
{
    public function __construct(
        private FileSystemUtils $fileSystemUtils,
    ) {}

    public function updateVersion(string $zipfilePath): string
    {
        $zip = $this->openZipFile($zipfilePath);
        $manifestContent = $this->extractManifest($zip);
        $zip->close();

        [$dom, $schemaVersion] = $this->findSchemaVersion($manifestContent);
        $this->validateAndUpdateVersion($dom, $schemaVersion);

        $newManifestContent = $dom->saveXML();
        if ($newManifestContent === false) {
            throw new RuntimeException('Failed to save XML document'); // @codeCoverageIgnore
        }

        // DOM does not allow changing an element's namespace URI via setAttribute,
        // so replace any remaining v3p1 namespace URI occurrences in the serialised XML.
        $newManifestContent = str_replace(
            'http://www.imsglobal.org/xsd/qti/qtiv3p1/imscp_v1p1',
            'http://www.imsglobal.org/xsd/qti/qtiv3p0/imscp_v1p1',
            $newManifestContent,
        );

        $tmpFile = $this->createTemporaryCopy($zipfilePath);
        $this->updateManifestInZip($tmpFile, $newManifestContent);

        return $tmpFile;
    }

    public function cleanup(string $tmpFile): void
    {
        FileSystemUtils::removeFile($tmpFile);
    }

    private function openZipFile(string $zipfilePath): ZipArchive
    {
        $zip = new ZipArchive();
        if ($zip->open($zipfilePath) !== true) {
            throw new RuntimeException('Could not open zip file: ' . $zipfilePath);
        }
        return $zip;
    }

    private function extractManifest(ZipArchive $zip): string
    {
        $manifestContent = $zip->getFromName('imsmanifest.xml');
        if ($manifestContent === false) {
            throw new RuntimeException('imsmanifest.xml not found in zip file');
        }
        return $manifestContent;
    }

    /**
     * @return array{DOMDocument, DOMNode}
     */
    private function findSchemaVersion(string $manifestContent): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$dom->loadXML($manifestContent)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new RuntimeException('Failed to load imsmanifest.xml: ' . print_r($errors, true));
        }
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($dom);

        $query = "//*[local-name()='manifest']/*[local-name()='metadata']/*[local-name()='schemaversion' or local-name()='version']";
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            throw new RuntimeException('schemaversion not found in imsmanifest.xml');
        }
        /** @var DOMNode $item */
        $item = $nodes->item(0);

        return [$dom, $item];
    }

    private function validateAndUpdateVersion(DOMDocument $dom, DOMNode $schemaVersion): void
    {
        $version = $schemaVersion->nodeValue;
        if ($version === '3.0.1') {
            $schemaVersion->nodeValue = '3.0.0';
        } elseif ($version !== '3.0.0') {
            throw new RuntimeException('Invalid schema version. Expected 3.0.1 or 3.0.0');
        }

        /** @var DOMElement $manifest */
        $manifest = $schemaVersion->parentNode?->parentNode;

        // Update xsi:schemaLocation references
        $schemaLocation = $manifest->getAttribute('xsi:schemaLocation');
        if ($schemaLocation) {
            $search = [
                'qtiv3p1/imscp_v1p1',
                'imsqti_asiv3p1_v1p0.xsd',
                'imsqtiv3p1_imscpv1p2_v1p0.xsd',
            ];
            $replace = [
                'qtiv3p0/imscp_v1p1',
                'imsqti_asiv3p0_v1p0.xsd',
                'imsqtiv3p0_imscpv1p2_v1p0.xsd',
            ];
            $schemaLocation = str_replace($search, $replace, $schemaLocation);
            $manifest->setAttribute('xsi:schemaLocation', $schemaLocation);
        }

        // Update resource types
        $xpath = new DOMXPath($dom);
        $query = "//*[local-name()='resource']";
        $resources = $xpath->query($query);
        if ($resources !== false) {
            foreach ($resources as $resource) {
                if ($resource instanceof DOMElement && $resource->hasAttribute('type')) {
                    $type = $resource->getAttribute('type');
                    $updatedType = str_replace('v3p1', 'v3p0', $type);
                    $resource->setAttribute('type', $updatedType);
                }
            }
        }
    }

    private function createTemporaryCopy(string $zipfilePath): string
    {
        $tempFilename = $this->fileSystemUtils->generateTempFilename();
        $tmpFile = $tempFilename . '.zip';
        FileSystemUtils::removeFile($tempFilename);
        if (!copy($zipfilePath, $tmpFile)) {
            throw new RuntimeException('Failed to create temporary copy of zip file'); // @codeCoverageIgnore
        }
        return $tmpFile;
    }

    private function updateManifestInZip(string $tmpFile, string $manifestContent): void
    {
        $newZip = new ZipArchive();
        if ($newZip->open($tmpFile) !== true) {
            throw new RuntimeException('Could not open new zip file: ' . $tmpFile); // @codeCoverageIgnore
        }
        $newZip->deleteName('imsmanifest.xml');
        if ($newZip->addFromString('imsmanifest.xml', $manifestContent) === false) {
            throw new RuntimeException('Failed to add updated imsmanifest.xml to zip file'); // @codeCoverageIgnore
        }
        $newZip->close();
    }
}
