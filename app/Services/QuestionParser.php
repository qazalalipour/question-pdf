<?php

namespace App\Services;

class QuestionParser
{
    public function parse(array $paragraphs): array
    {
        $questions = [];

        $currentQuestion = null;

        $mode = null;

        foreach ($paragraphs as $paragraph) {

            $content = $paragraph['content'] ?? [];

            if (empty($content)) {
                continue;
            }

            $text = trim(
                $this->extractPlainText(
                    $content
                )
            );

            if (
                $this->isQuestionStart($text)
            ) {

                if (
                    $currentQuestion !== null
                ) {
                    $questions[] =
                        $currentQuestion;
                }
                $currentQuestion = [
                    'number' =>
                        $this->extractQuestionNumber(
                            $text
                        ),

                    'content' =>
                        $this->removeQuestionPrefix(
                            $content
                        ),

                    'options' => [],

                    'correct_answer' => null,

                    'explanation' => [],
                ];

                $mode = 'question';

                continue;
            }

            if (
                $currentQuestion === null
            ) {
                continue;
            }

            if (
                $this->isOption($text)
            ) {

                $optionNumber =
                    $this->extractOptionNumber(
                        $text
                    );

                $currentQuestion[
                    'options'
                ][$optionNumber] = [
                    'content' =>
                        $this->removeOptionPrefix(
                            $content
                        ),
                ];

                $mode = 'options';

                continue;
            }

            if (
                $this->isCorrectAnswer($text)
            ) {

                $currentQuestion[
                    'correct_answer'
                ] =
                    $this->extractCorrectAnswer(
                        $text
                    );

                $mode = 'explanation';

                continue;
            }

            if (
                $mode === 'explanation'
            ) {

                $currentQuestion[
                    'explanation'
                ][] = [
                    'content' => $content,
                ];
            }
        }
        if (
            $currentQuestion !== null
        ) {
            $questions[] =
                $currentQuestion;
        }

        return $questions;
    }

    private function isQuestionStart(
        string $text
    ): bool {
        return preg_match(
            '/^(\d+)\s*[-ـ.]\s*/u',
            $text
        ) === 1;
    }

    private function extractQuestionNumber(
        string $text
    ): int {
        preg_match(
            '/^(\d+)\s*[-ـ.]/u',
            $text,
            $matches
        );

        return (int) (
            $matches[1] ?? 0
        );
    }

    private function removeQuestionPrefix(
        array $content
    ): array {

        $removePrefix = true;

        foreach (
            $content as &$item
        ) {

            if (
                $item['type'] !== 'text' ||
                !$removePrefix
            ) {
                continue;
            }

            $item['value'] =
                preg_replace(
                    '/^(\d+)\s*[-ـ.]\s*/u',
                    '',
                    $item['value'],
                    1,
                    $count
                );

            if (
                $count > 0
            ) {
                $removePrefix = false;
            }
        }

        return $content;
    }

    private function isOption(
        string $text
    ): bool {
        return preg_match(
            '/^[1-4]\s*[\)\.]\s*/u',
            $text
        ) === 1;
    }

    private function extractOptionNumber(
        string $text
    ): int {

        preg_match(
            '/^([1-4])\s*[\)\.]\s*/u',
            $text,
            $matches
        );

        return (int) (
            $matches[1] ?? 0
        );
    }
    private function removeOptionPrefix(
        array $content
    ): array {

        $removePrefix = true;

        foreach (
            $content as &$item
        ) {

            if (
                $item['type'] !== 'text' ||
                !$removePrefix
            ) {
                continue;
            }

            $item['value'] =
                preg_replace(
                    '/^[1-4]\s*[\)\.]\s*/u',
                    '',
                    $item['value'],
                    1,
                    $count
                );

            if (
                $count > 0
            ) {
                $removePrefix = false;
            }
        }

        return $content;
    }

    private function isCorrectAnswer(
        string $text
    ): bool {

        return preg_match(
            '/گزینه\s*[«"]?\s*(\d+)\s*[»"]?\s*(?:صحیح|درست)/u',
            $text
        ) === 1;
    }

    private function extractCorrectAnswer(
        string $text
    ): ?int {

        preg_match(
            '/گزینه\s*[«"]?\s*(\d+)\s*[»"]?\s*(?:صحیح|درست)/u',
            $text,
            $matches
        );

        return isset($matches[1])
            ? (int) $matches[1]
            : null;
    }

    private function extractPlainText(
        array $content
    ): string {

        $text = '';

        foreach (
            $content as $item
        ) {

            if (
                $item['type'] === 'text'
            ) {

                $text .=
                    $item['value'];

                continue;
            }

            if (
                $item['type'] === 'math'
            ) {

                $text .=
                    $this->extractMathTextFromXml(
                        $item['value']
                    );
            }
        }

        return $text;
    }

    private function extractMathTextFromXml(string $xml): string
    {
        if (trim($xml) === '') {
            return '';
        }

        $dom = new \DOMDocument();

        if (!@$dom->loadXML($xml)) {
            return '';
        }

        return $dom->textContent;
    }
}
