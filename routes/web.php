<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('questions/create', 'pages::questions.create')->name('questions.create');
    Route::livewire('q/{questionId}', 'pages::questions.show')->name('questions.show');
});

require __DIR__.'/settings.php';
