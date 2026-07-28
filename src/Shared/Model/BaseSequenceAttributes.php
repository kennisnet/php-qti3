<?php

declare(strict_types=1);

namespace Qti3\Shared\Model;

/**
 * The attribute set carried by the QTI base type `BaseSequenceDType` (see the
 * bundled ASI schema `imsqti_asiv3p0_v1p0.xsd`): the HTML-ish global attributes
 * (id, class, xml:lang, label, dir), the WAI-ARIA role, and the two open
 * attribute families (aria-* and the data-* extension attributes) as
 * name => value maps.
 *
 * `BaseSequence` extends `ARIABase` (role + aria-*) and adds the globals plus
 * the data-* extension family, so it is the shared anchor most body,
 * interaction and choice elements sit under.
 */
final readonly class BaseSequenceAttributes
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
