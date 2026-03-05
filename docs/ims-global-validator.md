# Using the IMS Global QTI Validator

This package ships with a lightweight XSD-based syntax validator (`QtiSchemaValidator`)
that requires no external dependencies. For production use, you may prefer the
**official IMS Global QTI 3.0 validator**, which performs more thorough conformance
checking against the full IMS specification.

## Background

`IQtiSyntaxValidator` is the interface responsible for *syntactic* validation of a QTI
package — checking that the package structure and XML content conform to the QTI 3.0
specification. Two implementations are available:

| Implementation | How it works | When to use |
|---|---|---|
| `QtiSchemaValidator` | XSD validation + structural checks, built in | Development, CI, testing |
| `ImsGlobalQtiSyntaxValidator` | Calls the IMS Global validator API via HTTP | Production, conformance testing |

## Setting up the IMS Global validator

The IMS Global validator runs as a Docker container. You need a valid IMS Global
membership or licence to obtain the image.

### 1. Obtain the Docker image

Contact [IMS Global](https://www.imsglobal.org/) to obtain access to the
`imsglobal/qti-validator` image. Once you have access:

```bash
docker pull imsglobal/qti-validator
```

### 2. Start the container

```bash
docker run -d \
  --name qti-validator \
  -p 8080:8080 \
  imsglobal/qti-validator
```

The validator is now available at `http://localhost:8080`.

### 3. Verify the container is running

```bash
curl -s http://localhost:8080/health
```

The endpoint accepts `POST` requests to `/api/validate?validatorId=Qti30Inspector`
with a multipart form body containing the QTI ZIP file as the `file` field.

## Wiring the IMS validator into `QtiClient`

Install a PSR-18 HTTP client and PSR-17 factories if you haven't already:

```bash
composer require symfony/http-client nyholm/psr7
```

Then pass `ImsGlobalQtiSyntaxValidator` as the fourth argument to `QtiClient`:

```php
use Qti3\QtiClient;
use Qti3\Package\Filesystem\FileSystemUtils;
use Qti3\Package\Filesystem\Zip\ZipArchiveFactory;
use Qti3\Package\Filesystem\Zip\ZipPackageFactory;
use Qti3\Package\Validator\ImsGlobalQtiSyntaxValidator;
use Symfony\Component\HttpClient\Psr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;

$psr17 = new Psr17Factory();
$httpClient = new Psr18Client();

$syntaxValidator = new ImsGlobalQtiSyntaxValidator(
    httpClient: $httpClient,
    requestFactory: $psr17,
    streamFactory: $psr17,
    zipPackageFactory: new ZipPackageFactory(new ZipArchiveFactory(), new FileSystemUtils()),
    endpointUrl: 'http://localhost:8080/api/validate',
);

$client = new QtiClient(
    filesystemPackageFactory: $filesystemPackageFactory,
    resourceValidator: $resourceValidator,
    resourceDownloader: $resourceDownloader,
    syntaxValidator: $syntaxValidator,          // replaces the default XSD validator
);
```

Now `$client->getQtiPackageValidator()` and `$client->getQtiSchemaValidator()` will
use the IMS Global validator for syntactic validation.

## Validating a ZIP directly

You can also call the syntax validator directly, without going through `QtiClient`:

```php
$errors = $syntaxValidator->validateZipPackage('/path/to/package.zip');

if ($errors->count() > 0) {
    foreach ($errors as $error) {
        echo $error . PHP_EOL;
    }
}
```

## Implementing your own validator

If you need a different validator (for example, a mock in tests or a validator that
calls a different endpoint), implement `IQtiSyntaxValidator` and pass your
implementation to `QtiClient`:

```php
use Qti3\Package\Validator\IQtiSyntaxValidator;
use Qti3\Package\Model\QtiPackage;
use Qti3\Shared\Collection\StringCollection;

class MyCustomValidator implements IQtiSyntaxValidator
{
    public function validateZipPackage(string $qtiPackageFilename): StringCollection
    {
        // your implementation
        return new StringCollection();
    }

    public function validate(QtiPackage $qtiPackage): StringCollection
    {
        // your implementation
        return new StringCollection();
    }
}
```
