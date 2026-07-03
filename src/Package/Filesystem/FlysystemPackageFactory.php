<?php

declare(strict_types=1);

namespace Qti3\Package\Filesystem;

use League\Flysystem\FilesystemOperator;
use Qti3\Package\Model\IItemEditor;
use Qti3\Package\Model\IPackageReader;
use Qti3\Package\Model\IPackageWriter;
use Qti3\Package\Service\IFilesystemPackageFactory;
use Qti3\Package\Service\IItemEditorFactory;
use Qti3\Package\Service\ItemIdentifierGenerator;
use Qti3\Package\Validator\AssessmentItemValidator;
use Qti3\Shared\Xml\Reader\XmlReader;

readonly class FlysystemPackageFactory implements IFilesystemPackageFactory, IItemEditorFactory
{
    public function __construct(
        private FilesystemOperator $filesystem,
    ) {}

    public function getReader(string $folder, bool $lazyLoading = true): IPackageReader
    {
        return new FlysystemPackageReader($folder, $this->filesystem, $lazyLoading);
    }

    public function getWriter(string $folder): IPackageWriter
    {
        return new FlysystemPackageWriter($folder, $this->filesystem);
    }

    public function getItemEditor(string $folder): IItemEditor
    {
        $xmlReader = new XmlReader();

        return new FlysystemItemEditor(
            $folder,
            $this->filesystem,
            $xmlReader,
            new AssessmentItemValidator($xmlReader),
            new ItemIdentifierGenerator(),
        );
    }
}
