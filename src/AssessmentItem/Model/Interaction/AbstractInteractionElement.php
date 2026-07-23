<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Model\Interaction;

use Qti3\Shared\Model\QtiElement;
use Qti3\Shared\Model\SharedAttributes;

abstract class AbstractInteractionElement extends QtiElement
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

    public function __construct(SharedAttributes $attributes = new SharedAttributes())
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
     * @return array<string,string>
     */
    protected function sharedAttributes(): array
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
