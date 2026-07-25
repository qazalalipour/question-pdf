<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuestionController;

Route::get('/', [QuestionController::class, 'index'])->name('questions.index');

Route::get('/questions/generate-pdf', [QuestionController::class, 'generatePdf'])->name('questions.pdf');

Route::get('/questions/{number}', [QuestionController::class, 'show'])->name('questions.show');

Route::get('/questions/{number}/image', [QuestionController::class, 'generateImage'])->name('questions.image');
