<?php

declare(strict_types=1);

namespace Qti3\Package\Validator;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Qti3\Package\Filesystem\FileSystemUtils;
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
 *       new ImsGlobalQtiSyntaxValidator($httpClient, $requestFactory, $streamFactory, 'http://localhost:8080/api/validate'),
 *   );
 */
final class ImsGlobalQtiSyntaxValidator implements IQtiSyntaxValidator
{
    private const string VALIDATOR_ID = 'Qti30Inspector';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $endpointUrl,
    ) {}

    /**
     * Validates a QTI ZIP package by posting it to the IMS Global validator endpoint.
     */
    public function validateZipPackage(string $qtiPackageFilename): StringCollection
    {
        if (!file_exists($qtiPackageFilename)) {
            return new StringCollection(['Package file does not exist: ' . $qtiPackageFilename]);
        }

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

        /**
         * @var array{
         *     errors: array<int, array{
         *         location: array{resource: string, line: int, column: int},
         *         message: string
         *     }>
         * } $result
         */
        $result = json_decode($response->getBody()->getContents(), true);

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
     *
     * Note: this requires a way to serialise the QtiPackage back to a ZIP.
     * Use {@see \Qti3\QtiClient::getQtiPackageBuilder()} to build the package
     * first, or inject a ZipWriter if your project already has one available.
     */
    public function validate(QtiPackage $qtiPackage): StringCollection
    {
        // TODO: write $qtiPackage to a temporary ZIP and delegate to validateZipPackage().
        // Example using QtiPackageBuilder:
        //
        //   $tmpFile = tempnam(sys_get_temp_dir(), 'qti_') . '.zip';
        //   $builder = $this->qtiPackageBuilder;      // inject via constructor
        //   $builder->build($qtiPackage, $tmpFile);
        //   try {
        //       return $this->validateZipPackage($tmpFile);
        //   } finally {
        //       FileSystemUtils::removeFile($tmpFile);
        //   }

        throw new \LogicException(
            sprintf(
                '%s::validate() is not implemented. Serialise the package to a ZIP first and call validateZipPackage() directly.',
                self::class,
            ),
        );
    }
}
