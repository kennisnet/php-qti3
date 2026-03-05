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
use Qti3\Package\Validator\ImsGlobalQtiSyntaxValidator;
use Qti3\Tests\Unit\Package\Model\QtiPackageMock;

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
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->zipPackageFactory = $this->createMock(ZipPackageFactory::class);

        $request = $this->createMock(RequestInterface::class);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->streamFactory->method('createStream')->willReturn($this->createMock(StreamInterface::class));

        $this->validator = new ImsGlobalQtiSyntaxValidator(
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $this->zipPackageFactory,
            'http://localhost:8080/api/validate',
        );
    }

    #[Test]
    public function validateZipPackageReturnsEmptyCollectionOnSuccess(): void
    {
        $tmpFile = $this->createTempFile();

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->mockResponse(json_encode(['errors' => []])));

        $errors = $this->validator->validateZipPackage($tmpFile);

        $this->assertCount(0, $errors);
    }

    #[Test]
    public function validateZipPackageFormatsErrorMessages(): void
    {
        $tmpFile = $this->createTempFile();

        $this->httpClient->method('sendRequest')->willReturn(
            $this->mockResponse(json_encode([
                'errors' => [
                    ['location' => ['resource' => '/item1.xml', 'line' => 5, 'column' => 3], 'message' => 'Invalid structure'],
                    ['location' => ['resource' => '/item2.xml', 'line' => 10, 'column' => 1], 'message' => 'Missing required attribute'],
                ],
            ])),
        );

        $errors = $this->validator->validateZipPackage($tmpFile);

        $this->assertCount(2, $errors);
        $this->assertSame('Location: /item1.xml [5,3] Description: `Invalid structure`', $errors->all()[0]);
        $this->assertSame('Location: /item2.xml [10,1] Description: `Missing required attribute`', $errors->all()[1]);
    }

    #[Test]
    public function validateZipPackageReturnsErrorWhenFileDoesNotExist(): void
    {
        $this->httpClient->expects($this->never())->method('sendRequest');

        $errors = $this->validator->validateZipPackage('/non/existent/package.zip');

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('/non/existent/package.zip', $errors->all()[0]);
    }

    #[Test]
    public function validateZipPackageReturnsErrorOnHttpClientException(): void
    {
        $tmpFile = $this->createTempFile();

        $this->httpClient->method('sendRequest')
            ->willThrowException($this->createMock(ClientExceptionInterface::class));

        $errors = $this->validator->validateZipPackage($tmpFile);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('IMS validator request failed', $errors->all()[0]);
    }

    #[Test]
    public function validatePackageWritesToZipAndDelegates(): void
    {
        $tmpFile = $this->createTempFile();

        $writer = $this->createMock(IPackageWriter::class);
        $writer->expects($this->once())->method('write');
        $writer->method('getPublicUrl')->willReturn($tmpFile);

        $this->zipPackageFactory->expects($this->once())
            ->method('getWriter')
            ->willReturn($writer);

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->mockResponse(json_encode(['errors' => []])));

        $errors = $this->validator->validate(new QtiPackageMock());

        $this->assertCount(0, $errors);
    }

    private function createTempFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'qti_test_');
        file_put_contents($path, 'fake zip content');
        $this->addToAssertionCount(0); // suppress "no assertions" if only used as fixture
        register_shutdown_function(static fn () => is_file($path) && unlink($path));
        return $path;
    }

    private function mockResponse(string $body): ResponseInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($body);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        return $response;
    }
}
