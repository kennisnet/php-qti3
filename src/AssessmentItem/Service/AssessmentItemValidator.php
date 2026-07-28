<?php

declare(strict_types=1);

namespace Qti3\AssessmentItem\Service;

use Qti3\AssessmentItem\Exception\InvalidAssessmentItemException;
use Qti3\Shared\Collection\StringCollection;
use Qti3\Shared\Xml\Reader\IXmlReader;
use Qti3\Shared\Xml\Reader\XmlParsingException;
use RuntimeException;

/**
 * Fast, structural validation of a single QTI 3 assessment item.
 *
 * Deliberately does not run full XSD validation: compiling the QTI 3 ASI schema
 * (which imports the large MathML schema) costs seconds per call and cannot be
 * cached between calls, which is far too slow for interactive editing. Full
 * schema/profile conformance remains a package-level, publish-time concern (see
 * {@see \Qti3\Package\Validator\ImsGlobalQtiSyntaxValidator}).
 *
 * Structural validation also works for interaction types the typed item parser
 * cannot handle (e.g. custom or upload interactions), which is why it is
 * string/DOM based.
 */
final readonly class AssessmentItemValidator implements IAssessmentItemValidator
{
    private const string ASI_NAMESPACE = 'http://www.imsglobal.org/xsd/imsqtiasi_v3p0';
    private const string ROOT_ELEMENT = 'qti-assessment-item';

    /** @var list<string> */
    private const array REQUIRED_ATTRIBUTES = ['identifier', 'title', 'time-dependent'];

    public function __construct(private IXmlReader $xmlReader) {}

    /**
     * @throws InvalidAssessmentItemException when the item XML is not structurally valid
     */
    public function validate(string $itemXml): void
    {
        $errors = new StringCollection();

        if (trim($itemXml) === '') {
            $errors->add('Item XML is empty');

            throw new InvalidAssessmentItemException($errors);
        }

        try {
            $dom = $this->xmlReader->read($itemXml);
        } catch (XmlParsingException $exception) {
            $errors->add('Invalid XML: ' . $exception->getMessage());

            throw new InvalidAssessmentItemException($errors);
        }

        $root = $dom->documentElement;
        if ($root === null) {
            throw new RuntimeException('Invalid item XML'); // @codeCoverageIgnore
        }

        if ($root->localName !== self::ROOT_ELEMENT) {
            $errors->add(sprintf('Root element must be %s, found: %s', self::ROOT_ELEMENT, $root->localName));
        }

        if ($root->namespaceURI !== self::ASI_NAMESPACE) {
            $errors->add(sprintf('Invalid namespace: %s, expected: %s', $root->namespaceURI ?? 'none', self::ASI_NAMESPACE));
        }

        foreach (self::REQUIRED_ATTRIBUTES as $attribute) {
            if (!$root->hasAttribute($attribute) || $root->getAttribute($attribute) === '') {
                $errors->add(sprintf('Missing required attribute: %s', $attribute));
            }
        }

        if (!$errors->isEmpty()) {
            throw new InvalidAssessmentItemException($errors);
        }
    }
}
