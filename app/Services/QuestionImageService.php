<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;

class QuestionImageService
{
    public function generate(array $question): string
    {
        $html = view('questions.show', compact('question'))->render();

        $path = storage_path('app/private/questions/' . $question['number'] . '.png');

        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        Browsershot::html($html)
            ->setChromePath(
                env(
                    'CHROME_PATH',
                    '/usr/bin/chromium'
                )
            )
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->timeout(60)
            ->windowSize(1200, 1)
            ->fullPage()
            ->save($path);

        return $path;
    }

    public function generateAll(array $questions): array
    {
        $paths = [];

        foreach ($questions as $question) {
            $paths[] = $this->generate($question);
        }

        return $paths;
    }
}
