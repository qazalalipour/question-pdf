<?php

namespace App\Services;

use FPDF;
use RuntimeException;

class QuestionPdfService
{
    public function generate(array $questions): string
    {
        $directory = storage_path('app/private/questions');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $pdfPath = $directory . '/questions.pdf';
        $pdf = new FPDF('P', 'mm', 'A4');

        $pageWidth = 210;
        $pageHeight = 297;
        $margin = 10;

        $contentWidth = $pageWidth - ($margin * 2);
        $contentHeight = $pageHeight - ($margin * 2);
        $currentY = $margin;

        $pdf->AddPage();

        foreach ($questions as $question) {
            $imagePath = $directory . '/' . $question['number'] . '.png';

            if (!is_file($imagePath)) {
                throw new RuntimeException('تصویر سؤال شماره ' . $question['number'] . ' پیدا نشد.');
            }

            $imageSize = getimagesize($imagePath);

            if ($imageSize === false) {
                throw new RuntimeException('امکان خواندن تصویر سؤال شماره ' . $question['number'] . ' وجود ندارد.');
            }

            $imageWidth = $imageSize[0];
            $imageHeight = $imageSize[1];

            $ratio = $contentWidth / $imageWidth;
            $width = $imageWidth * $ratio;
            $height = $imageHeight * $ratio;

            if ($currentY + $height > $pageHeight - $margin) {
                $pdf->AddPage();
                $currentY = $margin;
            }

            $pdf->Image($imagePath, $margin, $currentY, $width, $height);
            $currentY += $height;
        }

        $pdf->Output('F', $pdfPath);

        return $pdfPath;
    }
}
