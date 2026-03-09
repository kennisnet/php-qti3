# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Run all tests
composer test

# Run a single test file
vendor/bin/phpunit tests/Unit/AssessmentItem/Service/Parser/AssessmentItemParserTest.php

# Run a specific test method
vendor/bin/phpunit --filter testMethodName tests/Unit/...

# Run only unit tests
vendor/bin/phpunit --testsuite Unit

# Run only integration tests
vendor/bin/phpunit --testsuite Integration

# Static analysis
vendor/bin/phpstan analyse

# Install dependencies
composer install
```

## Architecture

The library parses, builds, and validates QTI 3.0 assessment packages. The central entry point is `QtiClient` (`src/QtiClient.php`), which acts as a lazy-loading service container for all services.

### Core Layers

**Parsing** (`src/AssessmentItem/Service/Parser/`): Each parser extends `AbstractParser` and handles one XML concept. `AssessmentItemParser` orchestrates the top-level parse, delegating to:
- `ItemBodyParser` → delegates to `InteractionParser`, `RubricBlockParser`, `FeedbackBlockParser`
- `ResponseDeclarationParser`, `OutcomeDeclarationParser`
- `ResponseProcessingParser` → delegates to `QtiExpressionParser`
- `StylesheetParser`, `ModalFeedbackParser`

**Models** (`src/AssessmentItem/Model/`): Immutable value objects implementing `IXmlElement`. `AssessmentItem` is the top-level model; `ItemBody` holds a `ContentNodeCollection` of interactions and inline content. Interactions implement `IContentNode`.

**Package handling** (`src/Package/`): `QtiPackageReader` reads ZIP or folder-based packages via `IFilesystemPackageFactory`. `QtiPackageBuilder` writes them back. `QtiPackageValidator` and `QtiSchemaValidator` validate structure and XML schema.

**Assessment tests** (`src/AssessmentTest/`): `TestBuilder` assembles an `AssessmentTest` from a parsed package.

**Response processing** (`src/AssessmentItem/Service/`): `ResponseProcessor` evaluates QTI response processing expressions using models from `QtiExpressionParser`.

### Key Interfaces

- `IXmlElement` — all XML-serializable models implement this
- `IContentNode` — marker for nodes that can appear inside `ItemBody`
- `IFilesystemPackageFactory` — abstracts filesystem access (enables testing without real files)
- `IResourceValidator` / `IResourceDownloader` — PSR-abstracted HTTP resource handling

### Supported QTI Interaction Types

`qti-choice-interaction`, `qti-text-entry-interaction`, `qti-extended-text-interaction`, `qti-gap-match-interaction`, `qti-hotspot-interaction`, `qti-hottext-interaction`, `qti-match-interaction`, `qti-order-interaction`, `qti-select-point-interaction`

### Testing Conventions

- Integration tests use `QtiClientTestCaseTrait` for temp-directory setup/teardown and `ZipPackageFixture` for ZIP-based test data.
- HTTP dependencies are mocked via a fake PSR HTTP client — no real network calls in tests.
- Test fixtures (sample QTI XML/packages) live in `fixtures/`.

### Requirements

PHP >= 8.4 with `dom`, `libxml`, and `zip` extensions.
