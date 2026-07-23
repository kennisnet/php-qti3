# Editing items in a package with `PackageEditor`

`PackageEditor` edits the assessment items of an existing QTI package: adding,
updating, removing and reordering items. It works **in place** on a
`QtiPackage` and does no filesystem I/O of its own — you load the package,
edit it, and save it yourself.

Every operation is *surgical*: it touches only the assessment test you name and
the single item that changes. Other items, media and metadata are left exactly
as they are, and packages with more than one assessment test are supported (you
select the test by its resource identifier).

Editing never refuses a less-than-perfect package. Instead, any construct the
typed models cannot hold — and would therefore drop when the XML is regenerated
— is reported as a **warning**. Parsing an item returns an `ItemParseResult`
(`item` + `warnings`); each editor operation returns an `EditResult` (`resource`
+ `warnings`, `resource` is null for reorder). Inspect the warnings to decide
whether the (partial) data loss is acceptable.

Each warning locates the offending element — source file, line number and an
identifier-based selector — so it can be traced back, e.g.:

```
AssessmentTest.xml: line 12 at /qti-assessment-test[@identifier='T']/qti-test-part[@identifier='tp']/qti-assessment-section[@identifier='s']: drops unsupported element <qti-selection>
```

The file name is added where it is known: `TestBuilder`/the editor prefix it for
the test being edited, and `parseFromString($xml, $source)` takes an optional
source label (e.g. a filename) for item warnings.

Obtain the editor from the `QtiClient` (see the main README for how to construct
the client):

```php
$editor = $qtiClient->getPackageEditor();
```

## Loading and saving

The editor never reads or writes files. Load the package first, then persist it
after editing:

```php
use Qti3\Package\Model\QtiPackage;

// Load an already extracted package folder into a QtiPackage.
$package = $qtiClient->getQtiPackageReader()->fromFilesystem('/tmp/my-package');

// ... edit the package (see below) ...

// Save it back to a folder (or use getZipPackageFactory() for a ZIP).
$qtiClient->getFilesystemPackageFactory()->getWriter('/tmp/my-package')->write($package);
```

Most operations need the identifier of the test resource you are editing. For a
single-test package:

```php
$testId = $package->getAssessmentTestIdentifier();
```

## Adding an item from an XML string

Items are passed to the editor as typed `AssessmentItem` models, so a raw XML
string is parsed first. By default the editor assigns the item the next free
`ITEMnnn` identifier — whatever identifier the XML carries is overwritten — and
returns the created resource.

```php
$itemXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<qti-assessment-item xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0"
                     identifier="PLACEHOLDER" title="Hoofdstad van Frankrijk"
                     time-dependent="false">
  <qti-item-body>
    <p>Wat is de hoofdstad van Frankrijk?</p>
  </qti-item-body>
</qti-assessment-item>
XML;

// Parse the string into a model (throws ParseError on malformed XML or an
// unsupported interaction type). $parsed->warnings lists anything the model
// could not keep from the source item.
$parsed = $qtiClient->getAssessmentItemParser()->parseFromString($itemXml);

// Add it; the editor assigns the identifier. $result->warnings covers the test
// being edited.
$result = $editor->addItemToTest($package, $testId, $parsed->item);

echo $result->resource->identifier;              // 'ITEM001'
echo (string) $result->resource->getMainFile();  // the item XML as written into the package

if (!$parsed->warnings->isEmpty() || !$result->warnings->isEmpty()) {
    // ... report data loss to the user ...
}
```

To choose the identifier yourself, pass `$identifier` (it must be unique within
the package). `getAvailableItemIdentifier()` returns the next free `ITEMnnn` if
you want to know it up front.

```php
$editor->addItemToTest($package, $testId, $parsed->item, identifier: 'VRAAG_1');
```

By default the item is appended to the section. Pass a zero-based `$position`
to insert it at a specific index instead:

```php
// Insert as the first item of the section.
$editor->addItemToTest($package, $testId, $parsed->item, position: 0);
```

## Updating an item

Updating replaces the content of an existing item, identified by the model's own
identifier. Only the item resource is rewritten — the test is not touched — so
an update works even when the surrounding test uses constructs the model cannot
represent.

```php
$itemXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<qti-assessment-item xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0"
                     identifier="ITEM001" title="Hoofdstad van Frankrijk"
                     time-dependent="false">
  <qti-item-body>
    <p>Wat is de hoofdstad van Frankrijk? (bijgewerkt)</p>
  </qti-item-body>
</qti-assessment-item>
XML;

$parsed = $qtiClient->getAssessmentItemParser()->parseFromString($itemXml);

$result = $editor->updateItem($package, $parsed->item); // $result->resource is the updated item
```

## Adding a resource (e.g. an uploaded file)

`addResource()` adds a standalone `webcontent` asset — an uploaded image, audio
clip, etc. — to the package, independent of any item. This supports a two-step
editor flow: the file is uploaded and added first, and the item XML that
references it arrives in a later request.

You choose the package-relative path the file should live at, and pass the
content as an `IFileContent` — `MemoryFileContent` for raw bytes, or a
lazy/streaming implementation. It is registered as a `webcontent` resource with
a fresh `RESOURCEnnn` identifier. Intermediate directories in the path are
created by the writer when the package is saved.

```php
use Qti3\Package\Model\FileContent\MemoryFileContent;

// Request 1: the uploaded bytes are added to the package at a path you choose.
$result = $editor->addResource($package, 'resources/photo.png', new MemoryFileContent($uploadedBytes));
$href = $result->resource->href; // 'resources/photo.png' — use this in the item XML
// ...save the package...
```

Pass `isBinary: false` for text-based assets. The path must stay inside the
package: an absolute path or a `..` segment is rejected, and adding a second
resource at a path that already exists throws — the caller is responsible for
choosing a unique path.

```php
// Request 2: an item update whose XML references $href reuses the resource
// added earlier — no duplicate resource is created, and the dependency is
// linked automatically.
$itemXml = str_replace('{{IMAGE}}', $href, $template);
$parsed = $qtiClient->getAssessmentItemParser()->parseFromString($itemXml);
$editor->updateItem($package, $parsed->item);
```

## Removing an item

```php
$editor->removeItemFromTest($package, $testId, 'ITEM001');
```

This drops the item ref from the test and deletes the item resource and its
file. If another test in the same package still references the item, the
resource is kept and only that test's ref is removed. Media the item introduced
is left in place.

## Reordering items

Pass the item identifiers in the order you want them. The list must contain
exactly the items currently in the test's section.

```php
$editor->reorderItemsInTest($package, $testId, ['ITEM003', 'ITEM001', 'ITEM002']);
```

## A complete example

```php
use Qti3\Package\Model\QtiPackage;

$editor = $qtiClient->getPackageEditor();

$package = $qtiClient->getQtiPackageReader()->fromFilesystem('/tmp/my-package');
$testId  = $package->getAssessmentTestIdentifier();

// Add two items; the editor assigns ITEM001 and ITEM002.
foreach (['Vraag 1', 'Vraag 2'] as $title) {
    $itemXml = sprintf(
        '<?xml version="1.0" encoding="UTF-8"?>'
        . '<qti-assessment-item xmlns="http://www.imsglobal.org/xsd/imsqtiasi_v3p0" '
        . 'identifier="new" title="%s" time-dependent="false">'
        . '<qti-item-body><p>%s</p></qti-item-body></qti-assessment-item>',
        $title,
        $title,
    );
    $editor->addItemToTest($package, $testId, $qtiClient->getAssessmentItemParser()->parseFromString($itemXml)->item);
}

// Reorder, then remove the first one.
$editor->reorderItemsInTest($package, $testId, ['ITEM002', 'ITEM001']);
$editor->removeItemFromTest($package, $testId, 'ITEM001');

// Persist the result.
$qtiClient->getFilesystemPackageFactory()->getWriter('/tmp/my-package')->write($package);
```

## Errors

| Situation | Exception |
|---|---|
| Malformed item XML, or an unsupported interaction type, passed to `parseFromString()` | `Qti3\AssessmentItem\Service\Parser\ParseError` |
| Unknown `$testId`, or updating/removing a non-existent item | `Qti3\Shared\Exception\ResourceNotFoundException` |
| Adding an item whose identifier already exists in the package | `Qti3\AssessmentTest\Exception\InvalidAssessmentTestException` |
| Reorder list that does not match the items in the test | `Qti3\AssessmentTest\Exception\InvalidItemOrderException` |

A construct the model cannot hold (outcome processing, test feedback, rubric
blocks, nested sections, a template declaration, an unconsumed attribute, ...)
is **not** an error: it is dropped on regeneration and reported through the
`warnings` on `ItemParseResult` / `EditResult`.

## Notes

- **Identifiers.** By default `addItemToTest()` assigns the next free `ITEMnnn`,
  overwriting whatever the item carries. Pass `$identifier` to use your own
  scheme (must be unique within the package); `getAvailableItemIdentifier()`
  returns the next free one if you want it up front.
- **Data loss is reported, not refused.** The parsers surface every construct
  they cannot faithfully round-trip as a warning, so editing an imperfect
  package succeeds while making the loss visible. (An unsupported *interaction
  type* still fails in the parser with a `ParseError`; see *Supported
  interactions* in the main README.)
- **Untouched items are never re-serialized**, so an unrelated item that uses an
  unsupported construct does not affect editing.
- **Media** referenced by an added or updated item is carried over when the file
  is already in the package, or registered as new webcontent otherwise, without
  duplicating resources. Only files already in the package, `data:` URIs and
  `http(s)` URLs are accepted as media sources; a local filesystem path in item
  content (e.g. `../secret` or `/etc/passwd`) is refused rather than read.
```
