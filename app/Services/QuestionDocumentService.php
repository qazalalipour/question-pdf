<?php

namespace App\Services;

class QuestionDocumentService
{
    public function __construct(
        private QuestionDocumentParser $documentParser,
        private QuestionParser $questionParser,
    ) {
    }

    public function process(string $filePath): array
    {
        $paragraphs = $this->documentParser->parse($filePath);

        return $this->questionParser->parse($paragraphs);
    }
}
