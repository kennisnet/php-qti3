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

**UC-P5: Validate a QTI package**

```php
$validator = $qtiClient->getQtiPackageValidator();
$errors = $validator->validate($qtiPackage);

if ($errors->count() > 0) {
    // $errors is a StringCollection of validation error messages
}
```

By default the library uses an XSD-based syntax validator (`QtiSchemaValidator`). To use the official **IMS Global QTI validator** (Docker image) instead, pass a custom `IQtiSyntaxValidator` implementation as the fourth argument to `QtiClient`. See [docs/ims-global-validator.md](docs/ims-global-validator.md) for setup instructions and a ready-to-use skeleton class.

**UC-P6: Add, update or reorder items in a package**

`getPackageEditor()` returns a `PackageEditor` that edits the assessment items of a `QtiPackage` **in place**. It does no filesystem I/O: you load the package, edit it, and save it yourself. Items are passed as typed `AssessmentItem` models — you build or parse them (see UC-I1). Adding an item assigns it the next free `ITEMnnn` identifier by default (or one you pass). Each operation is *surgical*: adding or reordering rewrites a single assessment test (named by its resource identifier `$testId`, so packages with more than one test are supported) and, for an add, appends one item resource; updating replaces a single item resource. Untouched items, media and metadata are left exactly as they are. Editing never refuses an imperfect package: a construct the model cannot hold is dropped on regeneration and reported through the returned `EditResult`'s `warnings` (parsing an item likewise returns an `ItemParseResult` with `item` + `warnings`).

> **See [docs/package-editor.md](docs/package-editor.md)** for worked examples of adding an item from an XML string, updating, removing and reordering items, an errors table and notes.

```php
$package = $qtiClient->getQtiPackageReader()->fromFilesystem('/tmp/folder');
$editor  = $qtiClient->getPackageEditor();
$parser  = $qtiClient->getAssessmentItemParser();

// The resource identifier of the test to edit. For a single-test package:
$testId = $package->getAssessmentTestIdentifier();

// Build the item model from your QTI 3 item XML string ($parsed->item), and
// inspect $parsed->warnings for anything the model could not keep.
$parsed = $parser->parseFromString($itemXml);

// Add it; the editor assigns the next free identifier. Returns an EditResult.
$added = $editor->addItemToTest($package, $testId, $parsed->item);
// $added->resource->identifier is 'ITEM001'; $added->warnings covers the edited test.

// Pass your own identifier and/or a zero-based position (default: next id, append).
$editor->addItemToTest($package, $testId, $parsed->item, identifier: 'ITEM042', position: 0);

// Update an existing item's content. The model's own identifier selects the
// item to replace, so parse XML that carries identifier="ITEM001".
$editor->updateItem($package, $parser->parseFromString($updatedItemXml)->item);

// Remove an item from the test.
$editor->removeItemFromTest($package, $testId, 'ITEM001');

// Reorder the items of the assessment test section.
$editor->reorderItemsInTest($package, $testId, ['ITEM002', 'ITEM001']);

// Persist the edited package (folder or ZIP).
$qtiClient->getFilesystemPackageFactory()->getWriter('/tmp/folder')->write($package);
```

Removing an item drops its ref from the named test and, unless another test still references it, deletes the item resource and its file; media the item introduced is left in place. Because editing is surgical, untouched items, media and metadata are left as they are — an unrelated item that uses a construct the typed models cannot represent does not affect editing. A construct the model cannot hold (outcome processing, test feedback, rubric blocks, nested sections, a template declaration, an unconsumed attribute, ...) is not refused: it is dropped when the XML is regenerated and reported via the `warnings` on `EditResult`/`ItemParseResult`. An unsupported *interaction type* still fails earlier, in the parser, with a `ParseError` (see *Supported interactions* below).

Adding an item whose identifier already exists in the package throws `InvalidAssessmentTestException`; editing a non-existent test or updating a non-existent item throws `ResourceNotFoundException`; an order that does not match the items in the test throws `InvalidItemOrderException`. Media that the added or updated item references is carried over (files already in the package) or registered as new webcontent, without duplicating resources.

### Assessment Test Level

**UC-T1: Generate test from package**

```php
$testBuilder = $qtiClient->getTestBuilder();
$result = $testBuilder->buildFromPackage($qtiPackage);
$test = $result->test;          // Qti3\AssessmentTest\Model\AssessmentTest
$warnings = $result->warnings;  // constructs the model could not keep
// Pass a test resource identifier as the second argument to select one test
// in a multi-test package: buildFromPackage($qtiPackage, $testId).
```

`buildFromPackage()` returns a `TestParseResult` (`test` + `warnings`). A construct the model cannot represent losslessly (outcome processing, test feedback, rubric blocks, nested sections, ...) is not refused: it is dropped on the round-trip and reported in `warnings`.

**UC-T2: Generate package from test**

```php
// $test is of type Qti3\AssessmentTest\Model\AssessmentTest
// $items is an array of Qti3\AssessmentItem\Model\AssessmentItem
$packageBuilder = $qtiClient->getQtiPackageBuilder();
$package = $packageBuilder->buildForTest($test, $items);
// $package is now of type Qti3\Package\Model\QtiPackage
```

### Assessment Item Level

**UC-I1: Parse item XML to model**

```php
$assessmentItemParser = $qtiClient->getAssessmentItemParser();

// From a DOMElement:
$result = $assessmentItemParser->parse($itemElement);

// Or directly from an XML string (throws ParseError on malformed XML):
$result = $assessmentItemParser->parseFromString($itemXml);

$item = $result->item;          // Qti3\AssessmentItem\Model\AssessmentItem
$warnings = $result->warnings;  // constructs the model could not keep from the source
```

Each warning locates the offending element (line number + identifier-based selector); pass a source label to `parseFromString($xml, $source)` to prefix them with a filename.

**UC-I2: Generate XML from item**

```php
// $item is of type Qti3\AssessmentItem\Model\AssessmentItem
$xmlBuilder = $qtiClient->getXmlBuilder();
$itemXml = $xmlBuilder->generateXmlFromObject($item);
// $itemXml is now of type DomDocument
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

### Supported interactions

The `AssessmentItem` parser supports exactly the interaction types listed below via the `InteractionParser` used by `ItemBodyParser`. Other QTI 3.0 interaction types (e.g. `qti-inline-choice-interaction`, `qti-associate-interaction`, `qti-slider-interaction`, `qti-media-interaction`, the graphic interactions) are **not** supported: parsing such an item throws a `ParseError`, and the item editor (UC-P6) refuses packages containing them.

- `qti-choice-interaction`
- `qti-text-entry-interaction`
- `qti-extended-text-interaction`
- `qti-gap-match-interaction`
- `qti-hotspot-interaction`
- `qti-hottext-interaction`
- `qti-match-interaction`
- `qti-order-interaction`
- `qti-select-point-interaction`

## Running Tests

You can run the unit tests with the following Composer command:

```bash
composer test
```
