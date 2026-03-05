<?php

declare(strict_types=1);

namespace Qti3\Tests\Unit\Package\Validator;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Qti3\Package\Filesystem\Zip\ZipPackageFactory;
use Qti3\Package\Model\IPackageWriter;
use Qti3\Package\Model\QtiPackage;
use Qti3\Package\Validator\ImsGlobalQtiSyntaxValidator;

class ImsGlobalQtiSyntaxValidatorTest extends TestCase
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private ZipPackageFactory $zipPackageFactory;
    private ImsGlobalQtiSyntaxValidator $validator;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createStub(RequestFactoryInterface::class);
        $this->streamFactory = $this->createStub(StreamFactoryInterface::class);
        $this->zipPackageFactory = $this->createMock(ZipPackageFactory::class);

        $request = $this->createStub(RequestInterface::class);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->streamFactory->method('createStream')->willReturn($this->createStub(StreamInterface::class));

        $this->validator = new ImsGlobalQtiSyntaxValidator(
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $this->zipPackageFactory,
            'http://localhost:8080/api/validate',
        );
    }

    #[Test]
    public function validateZipPackageReturnsErrorForNonExistentFile(): void
    {
        $errors = $this->validator->validateZipPackage('/non/existent/package.zip');

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Package file does not exist', (string) $errors->first());
    }

    #[Test]
    public function validateZipPackageReturnsValidationErrors(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'qti_test_');
        file_put_contents($tmpFile, 'fake-zip-content');

        try {
            $responseBody = $this->createStub(StreamInterface::class);
            $responseBody->method('getContents')->willReturn(json_encode([
                'errors' => [
                    [
                        'location' => ['resource' => 'item001.xml', 'line' => 10, 'column' => 5],
                        'message' => 'Missing required attribute',
                    ],
                    [
                        'location' => ['resource' => 'item002.xml', 'line' => 3, 'column' => 1],
                        'message' => 'Invalid element',
                    ],
                ],
            ]));

            $response = $this->createStub(ResponseInterface::class);
            $response->method('getBody')->willReturn($responseBody);

            $this->httpClient->method('sendRequest')->willReturn($response);

            $errors = $this->validator->validateZipPackage($tmpFile);

            $this->assertCount(2, $errors);
            $this->assertStringContainsString('item001.xml', (string) $errors->first());
            $this->assertStringContainsString('Missing required attribute', (string) $errors->first());
        } finally {
            unlink($tmpFile);
        }
    }

    #[Test]
    public function validateZipPackageReturnsEmptyCollectionWhenNoErrors(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'qti_test_');
        file_put_contents($tmpFile, 'fake-zip-content');

        try {
            $responseBody = $this->createStub(StreamInterface::class);
            $responseBody->method('getContents')->willReturn(json_encode(['errors' => []]));

            $response = $this->createStub(ResponseInterface::class);
            $response->method('getBody')->willReturn($responseBody);

            $this->httpClient->method('sendRequest')->willReturn($response);

            $errors = $this->validator->validateZipPackage($tmpFile);

            $this->assertCount(0, $errors);
        } finally {
            unlink($tmpFile);
        }
    }

    #[Test]
    public function validateZipPackageReturnsErrorWhenHttpClientThrows(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'qti_test_');
        file_put_contents($tmpFile, 'fake-zip-content');

        try {
            $exception = new class ('Connection refused') extends \RuntimeException implements ClientExceptionInterface {};
            $this->httpClient->method('sendRequest')->willThrowException($exception);

            $errors = $this->validator->validateZipPackage($tmpFile);

            $this->assertCount(1, $errors);
            $this->assertStringContainsString('IMS validator request failed', (string) $errors->first());
            $this->assertStringContainsString('Connection refused', (string) $errors->first());
        } finally {
            unlink($tmpFile);
        }
    }

    #[Test]
    public function validateDelegatesToValidateZipPackageAndCleansUp(): void
    {
        $qtiPackage = $this->createStub(QtiPackage::class);
        $tmpZipPath = sys_get_temp_dir() . '/test_validate_' . bin2hex(random_bytes(4)) . '.zip';

        $writer = $this->createMock(IPackageWriter::class);
        $writer->expects($this->once())->method('write')->with($qtiPackage);
        $writer->method('getPublicUrl')->willReturn($tmpZipPath);

        $this->zipPackageFactory->method('getWriter')->willReturn($writer);

        // The file won't exist at $tmpZipPath, so validateZipPackage will return the
        // "file does not exist" error — that's fine, we're testing the delegation flow
        $errors = $this->validator->validate($qtiPackage);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Package file does not exist', (string) $errors->first());
    }
}
