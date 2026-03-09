<?php

declare(strict_types=1);

namespace Qti3\Package\Validator;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Qti3\Package\Filesystem\FileSystemUtils;
use Qti3\Package\Filesystem\Zip\QtiPackageVersionUpdater;
use Qti3\Package\Filesystem\Zip\ZipPackageFactory;
use Qti3\Package\Model\QtiPackage;
use Qti3\Shared\Collection\StringCollection;

/**
 * Validates a QTI package against the official IMS Global QTI validator.
 *
 * The IMS Global validator runs as a Docker container and exposes an HTTP API.
 * See docs/ims-global-validator.md for setup instructions.
 *
 * This class requires a PSR-18 HTTP client and PSR-17 factories. Install any
 * compatible implementation, for example:
 *
 *   composer require symfony/http-client nyholm/psr7
 *
 * Wire this class instead of the default {@see QtiSchemaValidator} by passing
 * it as the fourth argument to {@see \Qti3\QtiClient::__construct()}:
 *
 *   $client = new QtiClient(
 *       $filesystemPackageFactory,
 *       $resourceValidator,
 *       $resourceDownloader,
 *       new ImsGlobalQtiSyntaxValidator(
 *           $httpClient,
 *           $requestFactory,
 *           $streamFactory,
 *           new ZipPackageFactory(new ZipArchiveFactory(), new FileSystemUtils()),
 *           'http://localhost:8080/api/validate',
 *           new QtiPackageVersionUpdater(new FileSystemUtils()),
 *       ),
 *   );
 */
final class ImsGlobalQtiSyntaxValidator implements IQtiSyntaxValidator
{
    private const string VALIDATOR_ID = 'Qti30Inspector';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ZipPackageFactory $zipPackageFactory,
        private readonly string $endpointUrl,
        private readonly QtiPackageVersionUpdater $versionUpdater,
    ) {}

    /**
     * Validates a QTI ZIP package by posting it to the IMS Global validator endpoint.
     */
    public function validateZipPackage(string $qtiPackageFilename): StringCollection
    {
        if (!file_exists($qtiPackageFilename)) {
            return new StringCollection(['Package file does not exist: ' . $qtiPackageFilename]);
        }

        $normalised = $this->versionUpdater->updateVersion($qtiPackageFilename);

        try {
            return $this->doValidateZipPackage($normalised);
        } finally {
            $this->versionUpdater->cleanup($normalised);
        }
    }

    private function doValidateZipPackage(string $qtiPackageFilename): StringCollection
    {
        $boundary = bin2hex(random_bytes(16));
        $filename = basename($qtiPackageFilename);
        $fileContents = file_get_contents($qtiPackageFilename);

        $body = implode("\r\n", [
            "--{$boundary}",
            "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"",
            'Content-Type: application/zip',
            '',
            $fileContents,
            "--{$boundary}--",
            '',
        ]);

        $stream = $this->streamFactory->createStream($body);

        $url = $this->endpointUrl . '?' . http_build_query(['validatorId' => self::VALIDATOR_ID]);

        $request = $this->requestFactory
            ->createRequest('POST', $url)
            ->withHeader('Content-Type', "multipart/form-data; boundary={$boundary}")
            ->withHeader('Accept', 'application/json')
            ->withBody($stream);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return new StringCollection(['IMS validator request failed: ' . $e->getMessage()]);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            return new StringCollection([
                sprintf('IMS validator returned HTTP %d: %s', $statusCode, $response->getBody()->getContents()),
            ]);
        }

        $result = json_decode($response->getBody()->getContents(), true);
        if (!is_array($result)) {
            return new StringCollection(['IMS validator returned invalid JSON response']);
        }

        return new StringCollection(
            array_map(
                fn(array $error): string => sprintf(
                    'Location: %s [%u,%u] Description: `%s`',
                    $error['location']['resource'],
                    $error['location']['line'],
                    $error['location']['column'],
                    $error['message'],
                ),
                $result['errors'] ?? [],
            ),
        );
    }

    /**
     * Validates a parsed QtiPackage by writing it to a temporary ZIP file and
     * forwarding to {@see validateZipPackage()}.
     */
    public function validate(QtiPackage $qtiPackage): StringCollection
    {
        $tmpZip = sys_get_temp_dir() . '/qti_package_' . bin2hex(random_bytes(8)) . '.zip';

        $writer = $this->zipPackageFactory->getWriter($tmpZip);
        $writer->write($qtiPackage);

        try {
            return $this->validateZipPackage($writer->getPublicUrl());
        } finally {
            FileSystemUtils::removeFile($tmpZip);
        }
    }
}
