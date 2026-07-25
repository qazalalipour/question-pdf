<?php

namespace App\Services;

use App\Services\OmmlToMathmlConverter;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class QuestionDocumentParser
{
    public function __construct(
        private OmmlToMathmlConverter $ommlToMathmlConverter,
    ) {
    }
    private const WORD_NAMESPACE =
        'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const MATH_NAMESPACE =
        'http://schemas.openxmlformats.org/officeDocument/2006/math';

    public function parse(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(
                'فایل موردنظر پیدا نشد.'
            );
        }

        $zip = new ZipArchive();

        if ($zip->open($filePath) !== true) {
            throw new RuntimeException(
                'امکان باز کردن فایل Word وجود ندارد.'
            );
        }

        $documentXml = $zip->getFromName(
            'word/document.xml'
        );

        $zip->close();

        if ($documentXml === false) {
            throw new RuntimeException(
                'فایل document.xml داخل فایل Word پیدا نشد.'
            );
        }

        return $this->parseDocumentXml(
            $documentXml
        );
    }

    private function parseDocumentXml(
        string $xml
    ): array {
        $dom = new DOMDocument();

        $dom->preserveWhiteSpace = true;

        if (!@$dom->loadXML($xml)) {
            throw new RuntimeException(
                'ساختار XML فایل Word قابل پردازش نیست.'
            );
        }

        $xpath = new DOMXPath($dom);

        $xpath->registerNamespace(
            'w',
            self::WORD_NAMESPACE
        );

        $xpath->registerNamespace(
            'm',
            self::MATH_NAMESPACE
        );

        $paragraphs = $xpath->query(
            '//w:body//w:p'
        );

        if ($paragraphs === false) {
            return [];
        }

        $result = [];

        foreach ($paragraphs as $paragraph) {

            if (!$paragraph instanceof DOMElement) {
                continue;
            }

            $content = $this->extractParagraphContent(
                $paragraph
            );

            if (empty($content)) {
                continue;
            }

            $result[] = [
                'content' => $content,
            ];
        }

        return $result;
    }
    private function extractParagraphContent(
        DOMElement $paragraph
    ): array {
        $content = [];

        foreach ($paragraph->childNodes as $node) {

            if (
                $node->nodeType !== XML_ELEMENT_NODE
            ) {
                continue;
            }

            $this->extractNodeContent(
                $node,
                $content
            );
        }

        return $this->mergeAdjacentText(
            $content
        );
    }

    private function extractNodeContent(
        DOMNode $node,
        array &$content
    ): void {

        if (
            $node->nodeType !== XML_ELEMENT_NODE
        ) {
            return;
        }

        if (
            $node->namespaceURI ===
            self::MATH_NAMESPACE &&
            $node->localName === 'oMath'
        ) {

            $content[] = [
                'type' => 'math',
                'value' => $this->ommlToMathmlConverter->convert(
                    $this->extractMathXml($node)
                ),
            ];

            return;
        }

        if (
            $node->namespaceURI ===
            self::WORD_NAMESPACE &&
            $node->localName === 'r'
        ) {

            $text = $this->extractRunText(
                $node
            );

            if ($text !== '') {
                $content[] = [
                    'type' => 'text',
                    'value' => $text,
                ];
            }

            foreach ($node->childNodes as $child) {

                if (
                    $child->nodeType !== XML_ELEMENT_NODE
                ) {
                    continue;
                }

                if (
                    $child->namespaceURI ===
                    self::MATH_NAMESPACE &&
                    $child->localName === 'oMath'
                ) {

                    $content[] = [
                        'type' => 'math',
                        'value' => $this->ommlToMathmlConverter->convert(
                            $this->extractMathXml($child)
                        ),
                    ];
                }
            }

            return;
        }

        foreach ($node->childNodes as $child) {

            if (
                $child->nodeType !== XML_ELEMENT_NODE
            ) {
                continue;
            }

            $this->extractNodeContent(
                $child,
                $content
            );
        }
    }

    private function extractRunText(
        DOMElement $run
    ): string {
        $text = '';

        foreach ($run->childNodes as $node) {

            if (
                $node->nodeType !== XML_ELEMENT_NODE
            ) {
                continue;
            }

            if (
                $node->localName === 't'
            ) {

                $text .= $node->textContent;

                continue;
            }

            if (
                $node->localName === 'tab'
            ) {

                $text .= "\t";

                continue;
            }

            if (
                $node->localName === 'br'
            ) {

                $text .= "\n";
            }
        }

        return $text;
    }

    private function extractMathXml(DOMNode $math): string
    {
        return $math->C14N();
    }
    private function mergeAdjacentText(
        array $content
    ): array {
        $result = [];

        foreach ($content as $item) {

            if (
                empty($result)
            ) {
                $result[] = $item;
                continue;
            }

            $lastIndex = count($result) - 1;

            if (
                $result[$lastIndex]['type'] === 'text' &&
                $item['type'] === 'text'
            ) {

                $result[$lastIndex]['value'] .=
                    $item['value'];

                continue;
            }

            $result[] = $item;
        }

        return $result;
    }
}
