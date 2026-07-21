# Editing items in a package with `PackageEditor`

`PackageEditor` edits the assessment items of an existing QTI package: adding,
updating, removing and reordering items. It works **in place** on a
`QtiPackage` and does no filesystem I/O of its own — you load the package,
edit it, and save it yourself.

Every operation is *surgical*: it touches only the assessment test you name and
the single item that changes. Other items, media and metadata are left exactly
as they are, and packages with more than one assessment test are supported (you
select the test by its resource identifier).

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

// Parse the string into a model (throws ParseError on malformed XML).
$item = $qtiClient->getAssessmentItemParser()->parseFromString($itemXml);

// Add it; the editor assigns the identifier.
$added = $editor->addItemToTest($package, $testId, $item);

echo $added->identifier;              // 'ITEM001'
echo (string) $added->getMainFile();  // the item XML as written into the package
```

To choose the identifier yourself, pass `$identifier` (it must be unique within
the package). `getAvailableItemIdentifier()` returns the next free `ITEMnnn` if
you want to know it up front.

```php
$editor->addItemToTest($package, $testId, $item, identifier: 'VRAAG_1');
```

By default the item is appended to the section. Pass a zero-based `$position`
to insert it at a specific index instead:

```php
// Insert as the first item of the section.
$editor->addItemToTest($package, $testId, $item, position: 0);
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

$item = $qtiClient->getAssessmentItemParser()->parseFromString($itemXml);

$editor->updateItem($package, $item);   // returns the updated item Resource
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
    $editor->addItemToTest($package, $testId, $qtiClient->getAssessmentItemParser()->parseFromString($itemXml));
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
| Malformed item XML passed to `parseFromString()` | `Qti3\AssessmentItem\Service\Parser\ParseError` |
| Unknown `$testId`, or updating/removing a non-existent item | `Qti3\Shared\Exception\ResourceNotFoundException` |
| Adding an item whose identifier already exists in the package | `Qti3\AssessmentTest\Exception\InvalidAssessmentTestException` |
| Reorder list that does not match the items in the test | `Qti3\AssessmentTest\Exception\InvalidItemOrderException` |
| Rewriting a test (add, remove, reorder) that contains constructs the model cannot represent (outcome processing, test feedback, rubric blocks, nested sections, ...) | `Qti3\Shared\Exception\UnsupportedQtiConstructException` |

## Notes

- **Identifiers.** By default `addItemToTest()` assigns the next free `ITEMnnn`,
  overwriting whatever the item carries. Pass `$identifier` to use your own
  scheme (must be unique within the package); `getAvailableItemIdentifier()`
  returns the next free one if you want it up front.
- **Items are validated by parsing.** There is no separate validation step in
  the editor: an item that parses into a model is representable. Item XML that
  uses an unsupported interaction type, a template declaration or an
  unrecognized response-processing template fails in the parser (see
  *Supported interactions* in the main README).
- **Untouched items are never re-serialized**, so an unrelated item that uses an
  unsupported construct does not block editing.
- **Media** referenced by an added or updated item is carried over when the file
  is already in the package, or registered as new webcontent otherwise, without
  duplicating resources. Only files already in the package, `data:` URIs and
  `http(s)` URLs are accepted as media sources; a local filesystem path in item
  content (e.g. `../secret` or `/etc/passwd`) is refused rather than read.
```
