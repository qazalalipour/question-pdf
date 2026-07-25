<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class OmmlToMathmlConverter
{
    private const OMML_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/math';

    private const WORD_NAMESPACE = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function convert(string $omml): string
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;

        if (!@$dom->loadXML($omml)) {
            return '';
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('m', self::OMML_NAMESPACE);

        $math = $xpath->query('//m:oMath')->item(0);

        if (!$math instanceof DOMElement) {
            return '';
        }

        $mathml = new DOMDocument('1.0', 'UTF-8');
        $mathml->formatOutput = false;

        $mathElement = $mathml->createElement('math');

        $mathElement->setAttribute('xmlns', 'http://www.w3.org/1998/Math/MathML');

        $mathElement->setAttribute('display', 'inline');

        $mathml->appendChild($mathElement);

        foreach ($this->convertNodeListGrouped($math->childNodes, $mathml) as $converted) {
            $mathElement->appendChild($converted);
        }

        return $mathml->saveXML($mathElement);
    }

    private function convertNode(
        DOMNode $node,
        DOMDocument $document
    ): ?DOMNode {

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return null;
        }

        $element = $node;

        if ($element->namespaceURI !== self::OMML_NAMESPACE) {
            return null;
        }

        return match ($element->localName) {
            'r' => $this->convertRun($element, $document),

            'f' => $this->convertFraction($element, $document),

            'rad' => $this->convertRadical($element, $document),

            'sSup' => $this->convertSuperscript($element, $document),

            'sSub' => $this->convertSubscript($element, $document),

            'sSubSup' => $this->convertSubSuperscript($element, $document),

            'nary' => $this->convertNary($element, $document),

            'd' => $this->convertDelimiter($element, $document),

            'acc' => $this->convertAccent($element, $document),

            'bar' => $this->convertBar($element, $document),

            'func' => $this->convertFunction($element, $document),

            'groupChr' => $this->convertGroupCharacter($element, $document),

            'eqArr' => $this->convertEquationArray($element, $document),

            'oMath' => $this->convertMath($element, $document),

            'oMathPara' => $this->convertMathParagraph($element, $document),

            'limLow' => $this->convertLimLow($element, $document),

            'limUpp' => $this->convertLimUpp($element, $document),

            default => $this->convertChildren($element, $document),
        };
    }

    private function convertRun(
        DOMElement $run,
        DOMDocument $document
    ): ?DOMNode {
        $text = '';
        foreach ($run->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 't') {
                $text .= $child->textContent;
            }
        }

        $text = trim($text);

        if ($text === '') {
            return null;
        }

        if (
            preg_match(
                '/^lim\s*([a-zA-Z]+)\s*(?:→|->)\s*(.+)$/u',
                $text,
                $matches
            )
        ) {
            $variable = trim($matches[1]);
            $value = trim($matches[2]);

            $munder = $document->createElement('munder');

            $lim = $document->createElement('mo');

            $lim->appendChild($document->createTextNode('lim'));

            $munder->appendChild($lim);

            $limit = $document->createElement('mrow');

            $variableElement = $document->createElement('mi');

            $variableElement->appendChild(
                $document->createTextNode($variable)
            );

            $limit->appendChild($variableElement);

            $arrow = $document->createElement('mo');

            $arrow->appendChild(
                $document->createTextNode('→')
            );

            $limit->appendChild($arrow);

            $valueElement = $document->createElement('mi');

            $valueElement->appendChild(
                $document->createTextNode($value)
            );

            $limit->appendChild($valueElement);

            $munder->appendChild($limit);

            return $munder;
        }

        $textElement = $document->createElement('mi');

        $textElement->appendChild(
            $document->createTextNode($text)
        );

        return $textElement;
    }

    private function convertFraction(
        DOMElement $fraction,
        DOMDocument $document
    ): DOMNode {
        $mfrac = $document->createElement('mfrac');

        $numerator = $this->findChild($fraction, 'num');

        $denominator = $this->findChild($fraction, 'den');

        if ($numerator) {
            $mfrac->appendChild(
                $this->convertContainer($numerator, $document)
            );
        }

        if ($denominator) {
            $mfrac->appendChild(
                $this->convertContainer($denominator, $document)
            );
        }

        return $mfrac;
    }

    private function convertRadical(
        DOMElement $radical,
        DOMDocument $document
    ): DOMNode {
        $degree = $this->findChild($radical, 'deg');

        $base = $this->findChild($radical, 'e');

        if ($degree) {
            $root = $document->createElement('mroot');

            if ($base) {
                $root->appendChild(
                    $this->convertContainer($base, $document)
                );
            }

            $root->appendChild(
                $this->convertContainer($degree, $document)
            );

            return $root;
        }

        $sqrt = $document->createElement('msqrt');

        if ($base) {
            $sqrt->appendChild(
                $this->convertContainer($base, $document)
            );
        }

        return $sqrt;
    }

    private function convertSuperscript(
        DOMElement $superscript,
        DOMDocument $document
    ): DOMNode {
        $msup = $document->createElement('msup');

        $base = $this->findChild(
            $superscript,
            'e'
        );

        $sup = $this->findChild(
            $superscript,
            'sup'
        );

        if ($base) {

            $msup->appendChild(
                $this->convertContainer(
                    $base,
                    $document
                )
            );
        }

        if ($sup) {

            $msup->appendChild(
                $this->convertContainer(
                    $sup,
                    $document
                )
            );
        }

        return $msup;
    }

    private function convertSubscript(
        DOMElement $subscript,
        DOMDocument $document
    ): DOMNode {

        $msub = $document->createElement(
            'msub'
        );

        $base = $this->findChild(
            $subscript,
            'e'
        );

        $sub = $this->findChild(
            $subscript,
            'sub'
        );

        if ($base) {

            $msub->appendChild(
                $this->convertContainer(
                    $base,
                    $document
                )
            );
        }

        if ($sub) {

            $msub->appendChild(
                $this->convertContainer(
                    $sub,
                    $document
                )
            );
        }

        return $msub;
    }

    private function convertLimLow(
        DOMElement $limLow,
        DOMDocument $document
    ): DOMNode {

        $munder = $document->createElement(
            'munder'
        );

        $base = $this->findChild(
            $limLow,
            'e'
        );

        $limit = $this->findChild(
            $limLow,
            'lim'
        );

        if ($base) {

            $munder->appendChild(
                $this->convertContainer(
                    $base,
                    $document
                )
            );
        }

        if ($limit) {

            $munder->appendChild(
                $this->convertContainer(
                    $limit,
                    $document
                )
            );
        }

        return $munder;
    }

    private function convertLimUpp(
        DOMElement $limUpp,
        DOMDocument $document
    ): DOMNode {

        $mover = $document->createElement(
            'mover'
        );

        $base = $this->findChild(
            $limUpp,
            'e'
        );

        $limit = $this->findChild(
            $limUpp,
            'lim'
        );

        if ($base) {

            $mover->appendChild(
                $this->convertContainer(
                    $base,
                    $document
                )
            );
        }

        if ($limit) {

            $mover->appendChild(
                $this->convertContainer(
                    $limit,
                    $document
                )
            );
        }

        return $mover;
    }

    private function convertSubSuperscript(
        DOMElement $node,
        DOMDocument $document
    ): DOMNode {

        $msubsup = $document->createElement(
            'msubsup'
        );

        $base = $this->findChild(
            $node,
            'e'
        );

        $sub = $this->findChild(
            $node,
            'sub'
        );

        $sup = $this->findChild(
            $node,
            'sup'
        );

        if ($base) {

            $msubsup->appendChild(
                $this->convertContainer(
                    $base,
                    $document
                )
            );
        }

        if ($sub) {

            $msubsup->appendChild(
                $this->convertContainer(
                    $sub,
                    $document
                )
            );
        }

        if ($sup) {

            $msubsup->appendChild(
                $this->convertContainer(
                    $sup,
                    $document
                )
            );
        }

        return $msubsup;
    }

    private function convertNary(
        DOMElement $nary,
        DOMDocument $document
    ): DOMNode {

        $operator = $this->getNaryOperator($nary);

        $sub = $this->findChild(
            $nary,
            'sub'
        );

        $sup = $this->findChild(
            $nary,
            'sup'
        );

        $base = $this->findChild(
            $nary,
            'e'
        );

        $operatorElement = $document->createElement(
            'mo'
        );

        $operatorElement->appendChild(
            $document->createTextNode(
                $operator
            )
        );

        $subElement = $sub
            ? $this->convertContainer(
                $sub,
                $document
            )
            : $document->createElement(
                'mrow'
            );

        $supElement = $sup
            ? $this->convertContainer(
                $sup,
                $document
            )
            : $document->createElement(
                'mrow'
            );

        $baseElement = $base
            ? $this->convertContainer(
                $base,
                $document
            )
            : $document->createElement(
                'mrow'
            );

        $munderover = $document->createElement(
            'munderover'
        );

        $munderover->appendChild(
            $operatorElement
        );

        $munderover->appendChild(
            $subElement
        );

        $munderover->appendChild(
            $supElement
        );

        $mrow = $document->createElement(
            'mrow'
        );

        $mrow->appendChild(
            $munderover
        );

        $mrow->appendChild(
            $baseElement
        );

        return $mrow;
    }

    private function getRunVertAlign(DOMElement $run): ?string
    {
        foreach ($run->childNodes as $child) {

            if (
                $child instanceof DOMElement &&
                $child->localName === 'rPr'
            ) {
                foreach ($child->childNodes as $rPrChild) {

                    if (
                        $rPrChild instanceof DOMElement &&
                        $rPrChild->localName === 'rPr'
                    ) {
                        foreach ($rPrChild->childNodes as $wordProp) {

                            if (
                                $wordProp instanceof DOMElement &&
                                $wordProp->localName === 'vertAlign'
                            ) {
                                return $wordProp->getAttributeNS(
                                    self::WORD_NAMESPACE,
                                    'val'
                                );
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    private function convertNodeListGrouped(
        \DOMNodeList $children,
        DOMDocument $document
    ): array {

        $elements = [];

        foreach ($children as $child) {

            if (
                $child->nodeType === XML_ELEMENT_NODE
            ) {
                $elements[] = $child;
            }
        }

        $results = [];

        $count = count($elements);

        $i = 0;

        while ($i < $count) {

            $current = $elements[$i];

            if (
                $current instanceof DOMElement &&
                $current->namespaceURI === self::OMML_NAMESPACE &&
                $current->localName === 'limLow'
            ) {

                $converted = $this->convertLimLow(
                    $current,
                    $document
                );

                if ($converted !== null) {
                    $results[] = $converted;
                }

                $i++;

                continue;
            }

            if (
                $current instanceof DOMElement &&
                $current->namespaceURI === self::OMML_NAMESPACE &&
                $current->localName === 'r'
            ) {

                $text = '';

                foreach ($current->childNodes as $child) {

                    if (
                        $child instanceof DOMElement &&
                        $child->localName === 't'
                    ) {
                        $text .= $child->textContent;
                    }
                }

                $text = trim($text);

                if (
                    preg_match(
                        '/^lim\s*([a-zA-Z]+)\s*(?:→|->)\s*(.+)$/u',
                        $text
                    )
                ) {

                    $results[] = $this->convertRun(
                        $current,
                        $document
                    );

                    $i++;

                    continue;
                }
            }

            $next = $elements[$i + 1] ?? null;

            if (
                $current instanceof DOMElement &&
                $current->namespaceURI === self::OMML_NAMESPACE &&
                $current->localName === 'r' &&
                $next instanceof DOMElement &&
                $next->namespaceURI === self::OMML_NAMESPACE &&
                $next->localName === 'r'
            ) {

                $vertAlign = $this->getRunVertAlign(
                    $next
                );

                if (
                    in_array(
                        $vertAlign,
                        [
                            'subscript',
                            'superscript'
                        ],
                        true
                    )
                ) {

                    $base = $this->convertNode(
                        $current,
                        $document
                    );

                    $script = $this->convertNode(
                        $next,
                        $document
                    );

                    if (
                        $base !== null &&
                        $script !== null
                    ) {

                        $wrapper = $document->createElement(
                            $vertAlign === 'subscript'
                            ? 'msub'
                            : 'msup'
                        );

                        $wrapper->appendChild(
                            $base
                        );

                        $wrapper->appendChild(
                            $script
                        );

                        $results[] = $wrapper;

                        $i += 2;

                        continue;
                    }
                }
            }

            $converted = $this->convertNode(
                $current,
                $document
            );

            if ($converted !== null) {
                $results[] = $converted;
            }

            $i++;
        }

        return $results;
    }

    private function convertDelimiter(
        DOMElement $delimiter,
        DOMDocument $document
    ): DOMNode {

        $mrow = $document->createElement('mrow');

        $properties = $this->findChild($delimiter, 'dPr');

        $beg = '(';
        $end = ')';

        if ($properties) {

            $begElement = $this->findChild($properties, 'begChr');
            $endElement = $this->findChild($properties, 'endChr');

            if ($begElement) {
                $beg = $begElement->hasAttributeNS(self::OMML_NAMESPACE, 'val')
                    ? $begElement->getAttributeNS(self::OMML_NAMESPACE, 'val')
                    : '(';
            }

            if ($endElement) {
                $end = $endElement->hasAttributeNS(self::OMML_NAMESPACE, 'val')
                    ? $endElement->getAttributeNS(self::OMML_NAMESPACE, 'val')
                    : ')';
            }
        }

        if ($beg !== '') {
            $left = $document->createElement('mo');
            $left->appendChild($document->createTextNode($beg));
            $mrow->appendChild($left);
        }

        foreach ($delimiter->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'e') {
                $mrow->appendChild(
                    $this->convertContainer($child, $document)
                );
            }
        }

        if ($end !== '') {
            $right = $document->createElement('mo');
            $right->appendChild($document->createTextNode($end));
            $mrow->appendChild($right);
        }

        return $mrow;
    }

    private function convertAccent(
        DOMElement $accent,
        DOMDocument $document
    ): DOMNode {

        $mover = $document->createElement(
            'mover'
        );

        $base = $this->findChild(
            $accent,
            'e'
        );

        $properties = $this->findChild(
            $accent,
            'accPr'
        );

        $char = 'ˆ';

        if ($properties) {

            $chr = $this->findChild(
                $properties,
                'chr'
            );

            if ($chr) {

                $char = $chr->getAttributeNS(
                    self::OMML_NAMESPACE,
                    'val'
                ) ?: 'ˆ';
            }
        }

        if ($base) {

            $mover->appendChild(
                $this->convertContainer(
                    $base,
                    $document
                )
            );
        }

        $mo = $document->createElement(
            'mo'
        );

        $mo->appendChild(
            $document->createTextNode(
                $char
            )
        );

        $mover->appendChild(
            $mo
        );

        return $mover;
    }

    private function convertBar(
        DOMElement $bar,
        DOMDocument $document
    ): DOMNode {

        $mover = $document->createElement(
            'mover'
        );

        $base = $this->findChild(
            $bar,
            'e'
        );

        if ($base) {

            $mover->appendChild(
                $this->convertContainer(
                    $base,
                    $document
                )
            );
        }

        $overline = $document->createElement(
            'mo'
        );

        $overline->appendChild(
            $document->createTextNode(
                '¯'
            )
        );

        $mover->appendChild(
            $overline
        );

        return $mover;
    }

    private function convertFunction(
        DOMElement $function,
        DOMDocument $document
    ): DOMNode {

        $mrow = $document->createElement('mrow');

        $functionName = $this->findChild(
            $function,
            'fName'
        );


        if ($functionName) {

            $limLow = $this->findChild(
                $functionName,
                'limLow'
            );

            if ($limLow) {

                $mrow->appendChild(
                    $this->convertLimLow(
                        $limLow,
                        $document
                    )
                );

            } else {

                $name = $this->extractText(
                    $functionName
                );

                if ($name !== '') {

                    $mi = $document->createElement(
                        'mi'
                    );

                    $mi->appendChild(
                        $document->createTextNode(
                            $name
                        )
                    );

                    $mrow->appendChild(
                        $mi
                    );
                }
            }
        }

        $argument = $this->findChild(
            $function,
            'e'
        );

        if ($argument) {

            $mrow->appendChild(
                $this->convertContainer(
                    $argument,
                    $document
                )
            );
        }

        return $mrow;
    }

    private function convertGroupCharacter(
        DOMElement $group,
        DOMDocument $document
    ): DOMNode {

        $mover = $document->createElement(
            'mover'
        );

        $base = $this->findChild(
            $group,
            'e'
        );

        if ($base) {

            $mover->appendChild(
                $this->convertContainer(
                    $base,
                    $document
                )
            );
        }

        $properties = $this->findChild(
            $group,
            'groupChrPr'
        );

        $char = '¯';

        if ($properties) {

            $chr = $this->findChild(
                $properties,
                'chr'
            );

            if ($chr) {

                $char = $chr->getAttributeNS(
                    self::OMML_NAMESPACE,
                    'val'
                ) ?: '¯';
            }
        }

        $mo = $document->createElement(
            'mo'
        );

        $mo->appendChild(
            $document->createTextNode(
                $char
            )
        );

        $mover->appendChild(
            $mo
        );

        return $mover;
    }

    private function convertEquationArray(
        DOMElement $array,
        DOMDocument $document
    ): DOMNode {

        $mtable = $document->createElement(
            'mtable'
        );

        foreach ($array->childNodes as $child) {

            if (
                $child instanceof DOMElement &&
                $child->localName === 'e'
            ) {

                $mtr = $document->createElement(
                    'mtr'
                );

                $mtd = $document->createElement(
                    'mtd'
                );

                $mtd->appendChild(
                    $this->convertContainer(
                        $child,
                        $document
                    )
                );

                $mtr->appendChild(
                    $mtd
                );

                $mtable->appendChild(
                    $mtr
                );
            }
        }

        return $mtable;
    }

    private function convertMath(DOMElement $math, DOMDocument $document): DOMNode
    {
        $mrow = $document->createElement('mrow');

        foreach ($this->convertNodeListGrouped($math->childNodes, $document) as $node) {
            $mrow->appendChild($node);
        }

        return $mrow;
    }

    private function convertMathParagraph(
        DOMElement $mathParagraph,
        DOMDocument $document
    ): DOMNode {

        return $this->convertChildren(
            $mathParagraph,
            $document
        );
    }

    private function convertContainer(DOMElement $container, DOMDocument $document): DOMNode
    {
        $mrow = $document->createElement('mrow');

        foreach ($this->convertNodeListGrouped($container->childNodes, $document) as $node) {
            $mrow->appendChild($node);
        }

        return $mrow;
    }

    private function convertChildren(DOMElement $element, DOMDocument $document): DOMNode
    {
        $mrow = $document->createElement('mrow');

        foreach ($this->convertNodeListGrouped($element->childNodes, $document) as $node) {
            $mrow->appendChild($node);
        }

        return $mrow;
    }

    private function findChild(
        DOMElement $parent,
        string $name
    ): ?DOMElement {

        foreach ($parent->childNodes as $child) {

            if (
                $child instanceof DOMElement &&
                $child->namespaceURI ===
                self::OMML_NAMESPACE &&
                $child->localName === $name
            ) {
                return $child;
            }
        }

        return null;
    }

    private function extractText(
        DOMElement $element
    ): string {

        $text = '';

        foreach ($element->getElementsByTagNameNS(
            self::OMML_NAMESPACE,
            't'
        ) as $node) {

            $text .= $node->textContent;
        }

        return $text;
    }

    private function getNaryOperator(DOMElement $nary): string
    {
        $properties = $this->findChild($nary, 'naryPr');

        if ($properties) {
            $chr = $this->findChild($properties, 'chr');

            if ($chr) {
                $operator = $chr->getAttributeNS(self::OMML_NAMESPACE, 'val');

                if ($operator !== '') {
                    return $operator;
                }
            }
        }
        return '∫';
    }
}
