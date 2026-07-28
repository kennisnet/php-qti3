<?php

declare(strict_types=1);

namespace Qti3\Shared\Model;

/**
 * Base for any QTI element that carries the `BaseSequence` attribute set — the
 * interactions, their choices/gaps/prompts/labels, and (in principle) any other
 * body element the QTI base types sit under.
 *
 * It accepts a single {@see BaseSequenceAttributes} value object and unpacks it into
 * individual typed, readonly properties, so every element exposes e.g.
 * `$element->id`, `$element->class`, `$element->role` directly, plus the open
 * aria-* and data-* maps.
 *
 * The parser only fills the attributes the schema permits for a given element
 * (see {@see \Qti3\AssessmentItem\Service\Parser\AbstractParser::readBaseSequenceAttributes()});
 * anything else is dropped, so an unsupported attribute is never carried on the
 * model nor re-emitted.
 */
abstract class AbstractBaseSequenceElement extends QtiElement
{
    public readonly ?string $id;

    public readonly ?string $class;

    public readonly ?string $xmlLang;

    public readonly ?string $label;

    public readonly ?string $dir;

    public readonly ?string $role;

    /** @var array<string,string> */
    public readonly array $ariaAttributes;

    /** @var array<string,string> */
    public readonly array $dataAttributes;

    public function __construct(BaseSequenceAttributes $attributes = new BaseSequenceAttributes())
    {
        $this->id = $attributes->id;
        $this->class = $attributes->class;
        $this->xmlLang = $attributes->xmlLang;
        $this->label = $attributes->label;
        $this->dir = $attributes->dir;
        $this->role = $attributes->role;
        $this->ariaAttributes = $attributes->ariaAttributes;
        $this->dataAttributes = $attributes->dataAttributes;
    }

    /**
     * The base-sequence attributes in serialization form, to be merged into the
     * concrete element's own attributes(). Only attributes that are actually
     * set are returned, so absent ones are never emitted.
     *
     * @return array<string,string>
     */
    protected function baseSequenceAttributes(): array
    {
        $named = array_filter([
            'id' => $this->id,
            'class' => $this->class,
            'xml:lang' => $this->xmlLang,
            'label' => $this->label,
            'dir' => $this->dir,
            'role' => $this->role,
        ], static fn (?string $value): bool => $value !== null);

        return [
            ...$named,
            ...$this->ariaAttributes,
            ...$this->dataAttributes,
        ];
    }
}
