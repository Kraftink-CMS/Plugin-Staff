<?php

use Illuminate\Support\Facades\Route;
use KraftInk\Plugins\Staff\Http\Controllers\DocumentController;

Route::prefix('staff')
    ->name('plugins.staff.public.')
    ->middleware(['web', 'permission:staff.view'])
    ->group(function () {
        Route::name('documents.')->group(function () {
            Route::get('/', [DocumentController::class, 'index'])
                ->middleware('capability:extension,staff')
                ->name('index');
            Route::get('/{slug}', [DocumentController::class, 'show'])->name('show');
        });
    });
