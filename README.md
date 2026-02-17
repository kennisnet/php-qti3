# PHP QTI 3.0 Library

This library provides functionality for reading, writing and manipulating QTI 3.0 packages, assessment tests and assessment items.

## Installation

You can install the library via Composer:

```bash
composer require wikiwijs/php-qti3
```

## Usage

The library uses the `QtiClient` as a service container to provide access to various services.

### Initializing the QtiClient

To use the library, you first need to initialize the `QtiClient` with the required dependencies:

```php
use Qti3\QtiClient;

$qtiClient = new QtiClient(
    $filesystemPackageFactory, // Instance of Qti3\Package\Service\IFilesystemPackageFactory
    $resourceValidator,        // Instance of Qti3\Package\Validator\Resource\IResourceValidator
    $resourceDownloader,       // Instance of Qti3\Package\Service\IResourceDownloader
);
```

### QTI Package Level

**UC-P1: Import QTI3 package in ZIP format to package object**

```php
$qtiPackageReader = $qtiClient->getQtiPackageReader();
$qtiPackage = $qtiPackageReader->fromZip('/tmp/qti3.zip');
// $qtiPackage is now of type Qti3\Package\Model\QtiPackage
```

**UC-P2: Import QTI3 package from directory to package object**

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

**UC-T1: Generate test from package (does not exist yet)**

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

**UC-I1: Generate item from XML (does not exist yet)**

```php
// Note: itemParser is not directly exposed by QtiClient yet
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

You can run the unit tests using the following Composer command:

```bash
composer test
```
