<?php

use App\Http\Controllers\TagRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('questions/create', 'pages::questions.create')->name('questions.create');
    Route::livewire('q/{questionId}', 'pages::questions.show')->name('questions.show');
    Route::livewire('q/{questionId}/round', 'pages::questions.start-round')->name('questions.start-round');
});

Route::middleware('throttle:30,1')->group(function () {
    Route::livewire('t/{code}', 'pages::tags.show')->name('tags.show');
    Route::post('t/{code}', TagRecordController::class)->name('tags.record');
});

require __DIR__.'/settings.php';
