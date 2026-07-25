<?php

namespace App\Http\Controllers;

use App\Services\QuestionDocumentService;
use App\Services\QuestionImageService;
use App\Services\QuestionPdfService;

class QuestionController extends Controller
{
    public function __construct(
        private QuestionDocumentService $documentService,
        private QuestionImageService $imageService,
        private QuestionPdfService $pdfService,
    ) {
    }

    public function index()
    {
        $questions = $this->documentService->process(
            storage_path('app/private/input/questions.docx')
        );

        return view('questions.index', compact('questions'));
    }

    public function show(int $number)
    {
        $questions = $this->documentService->process(
            storage_path('app/private/input/questions.docx')
        );

        $question = collect($questions)->firstWhere('number', $number);

        abort_if($question === null, 404);

        return view('questions.show', compact('question'));
    }

    public function generateImage(int $number)
    {
        $questions = $this->documentService->process(
            storage_path('app/private/input/questions.docx')
        );

        $question = collect($questions)->firstWhere('number', $number);

        abort_if($question === null, 404);

        $path = $this->imageService->generate($question);

        return response()->download($path);
    }

    public function generatePdf()
    {
        $questions = $this->documentService->process(
            storage_path('app/private/input/questions.docx')
        );

        $path = $this->pdfService->generate($questions);

        return response()->download($path);
    }
}
