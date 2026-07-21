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

`getPackageEditor()` returns a `PackageEditor` that edits the assessment items of a `QtiPackage` **in place**. It does no filesystem I/O: you load the package, edit it, and save it yourself. Items are passed as typed `AssessmentItem` models — you build or parse them (see UC-I1) and own their identifiers; `getAvailableItemIdentifier()` vends a free one. Each operation is *surgical*: adding or reordering rewrites a single assessment test (named by its resource identifier `$testId`, so packages with more than one test are supported) and, for an add, appends one item resource; updating replaces a single item resource. Untouched items, media and metadata are left exactly as they are.

```php
$package = $qtiClient->getQtiPackageReader()->fromFilesystem('/tmp/folder');
$editor  = $qtiClient->getPackageEditor();
$parser  = $qtiClient->getAssessmentItemParser();

// The resource identifier of the test to edit. For a single-test package:
$testId = $package->getAssessmentTestIdentifier();

// Build the item model from your QTI 3 item XML string. Give it a free,
// package-unique identifier (the item carries its own id into the package).
$identifier = $editor->getAvailableItemIdentifier($package); // e.g. 'ITEM001'
$item = $parser->parseFromString($itemXml); // $itemXml carries identifier="ITEM001"

// Add the item; the item's own identifier is used. Returns the item Resource.
$added = $editor->addItemToTest($package, $testId, $item);
// $added->identifier is 'ITEM001'; (string) $added->getMainFile() is the item XML as written.

// Insert at a specific zero-based position in the section (default: append).
$editor->addItemToTest($package, $testId, $item, position: 0);

// Update an existing item's content (identified by the model's own identifier).
$editor->updateItem($package, $item);

// Remove an item from the test.
$editor->removeItemFromTest($package, $testId, 'ITEM001');

// Reorder the items of the assessment test section.
$editor->reorderItemsInTest($package, $testId, ['ITEM002', 'ITEM001']);

// Persist the edited package (folder or ZIP).
$qtiClient->getFilesystemPackageFactory()->getWriter('/tmp/folder')->write($package);
```

Removing an item drops its ref from the named test and, unless another test still references it, deletes the item resource and its file; media the item introduced is left in place. Because editing is surgical, untouched items, media and metadata are left as they are — an unrelated item that uses a construct the typed models cannot represent no longer blocks editing, and updating an item works even when its surrounding test does. A test that is *rewritten* (add, reorder) must fit the subset the `AssessmentTest` model can represent: a test containing outcome processing, test feedback, rubric blocks or nested sections is refused with `UnsupportedQtiConstructException` (see UC-T1). Items are supplied as models, so they are representable by construction — parsing item XML that uses an unsupported interaction type (see *Supported interactions* below), a template declaration or an unrecognized response-processing template fails earlier, in the parser.

Adding an item whose identifier already exists in the package throws `InvalidAssessmentTestException`; editing a non-existent test or updating a non-existent item throws `ResourceNotFoundException`; an order that does not match the items in the test throws `InvalidItemOrderException`. Media that the added or updated item references is carried over (files already in the package) or registered as new webcontent, without duplicating resources.

See [docs/package-editor.md](docs/package-editor.md) for worked examples of adding, updating, removing and reordering items.

### Assessment Test Level

**UC-T1: Generate test from package**

```php
$testBuilder = $qtiClient->getTestBuilder();
$test = $testBuilder->buildFromPackage($qtiPackage);
// $test is now of type Qti3\AssessmentTest\Model\AssessmentTest
// Pass a test resource identifier as the second argument to select one test
// in a multi-test package: buildFromPackage($qtiPackage, $testId).
```

`buildFromPackage()` refuses a test that contains constructs the model cannot represent losslessly (outcome processing, test feedback, rubric blocks, nested sections, ...) with `UnsupportedQtiConstructException`, rather than silently dropping them on the round-trip.

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
$item = $assessmentItemParser->parse($itemElement);

// Or directly from an XML string (throws ParseError on malformed XML):
$item = $assessmentItemParser->parseFromString($itemXml);
// $item is now of type Qti3\AssessmentItem\Model\AssessmentItem
```

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
