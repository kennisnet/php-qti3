<?php

declare(strict_types=1);

namespace Qti3\Shared\Model;

/**
 * The attributes the QTI base types share across body, interaction and choice
 * elements: the HTML-ish global attributes (id, class, xml:lang, label, dir),
 * the WAI-ARIA role, and the two open attribute families (aria-* and the data-*
 * extension attributes) as name => value maps.
 *
 * These are not specific to any one element kind; they come from the shared
 * base types (ARIABase, BaseSequence, ...) that sit under most of the schema.
 */
final readonly class SharedAttributes
{
    /**
     * @param array<string,string> $ariaAttributes aria-* attributes, verbatim
     * @param array<string,string> $dataAttributes data-* extension attributes, verbatim
     */
    public function __construct(
        public ?string $id = null,
        public ?string $class = null,
        public ?string $xmlLang = null,
        public ?string $label = null,
        public ?string $dir = null,
        public ?string $role = null,
        public array $ariaAttributes = [],
        public array $dataAttributes = [],
    ) {}
}
