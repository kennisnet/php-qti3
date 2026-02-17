# PHP QTI 3.0 Library

This library provides functionality for reading, writing and manipulating QTI 3.0 packages, assessment tests and assessment items.

## Installation

You can install the library via Composer:

```bash
composer require wikiwijs/php-qti3
```

## Usage

The library uses the `QtiClient` as a service container for accessing various services.

### Initializing the QtiClient

To use the library, you first need to initialize the `QtiClient` with the required dependencies. The library provides default implementations using PSR interfaces and Flysystem.

#### Required implementations

The `QtiClient` expects three implementations:

1.  **IFilesystemPackageFactory**: For reading and writing files to a (temporary) file system.
2.  **IResourceValidator**: For validating external resources (e.g. URLs).
3.  **IResourceDownloader**: For downloading external resources to the local file system.

#### Example with default implementations

The implementations below are available in the library but may require additional composer packages (see the `suggest` section in `composer.json`).

```php
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Filesystem;
use Qti3\QtiClient;
use Qti3\Package\Filesystem\FlysystemPackageFactory;
use Qti3\Package\Validator\Resource\PsrHttpClientResourceValidator;
use Qti3\Package\Downloader\Resource\PsrHttpClientResourceDownloader;
use Qti3\Package\Filesystem\FileSystemUtils;

// 1. Setup Flysystem (e.g. local file system)
// Required: composer require league/flysystem
$adapter = new LocalFilesystemAdapter('/tmp/qti-data');
$filesystem = new Filesystem($adapter);
$filesystemPackageFactory = new FlysystemPackageFactory($filesystem);

// 2. Setup PSR-18 HTTP Client and PSR-17 Request Factory
// E.g. Symfony's HTTP Client: composer require symfony/http-client psr/http-client nyholm/psr7
$httpClient = new \Symfony\Component\HttpClient\Psr18Client();
$requestFactory = new \Nyholm\Psr7\Factory\Psr17Factory();

// 3. Initialize the validator and downloader
$resourceValidator = new PsrHttpClientResourceValidator($httpClient, $requestFactory);
$resourceDownloader = new PsrHttpClientResourceDownloader(
    new FileSystemUtils(),
    $httpClient,
    $requestFactory,
    '/tmp/qti-data' // Folder where downloads are stored
);

// 4. Create the QtiClient
$qtiClient = new QtiClient(
    $filesystemPackageFactory,
    $resourceValidator,
    $resourceDownloader,
);
```

### QTI Package Level

**UC-P1: Import QTI3 package in ZIP format to package object**

```php
$qtiPackageReader = $qtiClient->getQtiPackageReader();
$qtiPackage = $qtiPackageReader->fromZip('/tmp/qti3.zip');
// $qtiPackage is now of type Qti3\Package\Model\QtiPackage
```

**UC-P2: Import QTI3 package from folder to package object**

```php
$qtiPackageReader = $qtiClient->getQtiPackageReader();
$qtiPackage = $qtiPackageReader->fromFilesystem('/tmp/folder');
// $qtiPackage is now of type Qti3\Package\Model\QtiPackage
```

**UC-P3: Generate ZIP file from package object**

```php
$zipPackageFactory = $qtiClient->getZipPackageFactory();
$writer = $zipPackageFactory->getWriter('/tmp/qti3.zip');
$writer->write($qtiPackage);
```

**UC-P4: Generate folder from package object**

```php
$filesystemPackageFactory = $qtiClient->getFilesystemPackageFactory();
$writer = $filesystemPackageFactory->getWriter('/tmp/folder');
$writer->write($qtiPackage);
```

### Assessment Test Level

**UC-T1: Generate test from package (not yet implemented)**

```php
// Note: buildFromPackage method is not yet implemented in QtiPackageBuilder
// $test = $testBuilder->buildFromPackage($qtiPackage, $testIdentifier);
```

**UC-T2: Generate package from test**

```php
// $test is of type Qti3\AssessmentTest\Model\AssessmentTest
// $items is an array of Qti3\AssessmentItem\Model\AssessmentItem
$packageBuilder = $qtiClient->getQtiPackageBuilder();
$package = $packageBuilder->buildForTest($test, $items);
// $package is now of type Qti3\Package\Model\QtiPackage
```

### Assessment Item Level

**UC-I1: Generate item from XML (not yet implemented)**

```php
// Note: itemParser is not yet directly exposed by QtiClient
// $item = $itemParser->parse($itemXml);
```

**UC-I2: Generate XML from item**

```php
$xmlBuilder = $qtiClient->getXmlBuilder();
$itemXml = $xmlBuilder->generateXmlFromObject($item)->saveXML();
// $itemXml is now of type string
```

**UC-I3: Response processing**

```php
// $responses is an associative array with response-identifier->value
$responseProcessor = $qtiClient->getResponseProcessor();
$itemState = $responseProcessor->initItemState($itemXml);
$responseProcessor->processResponses($itemState, $responses);
$outcomes = $itemState->outcomeSet->outcomes;
// $outcomes is now an associative array with outcome-identifier->value
```

## Running Tests

You can run the unit tests with the following Composer command:

```bash
composer test
```
