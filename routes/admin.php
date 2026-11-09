<?php

use Illuminate\Support\Facades\Route;
use KraftInk\Plugins\Staff\Http\Controllers\Admin\DisplayController;
use KraftInk\Plugins\Staff\Http\Controllers\Admin\OrderingController;
use KraftInk\Plugins\Staff\Http\Controllers\Admin\StaffController;
use KraftInk\Plugins\Staff\Http\Controllers\DocumentController;

Route::prefix('admin/plugins/staff')
    ->name('plugins.staff.admin.')
    ->middleware(['web', 'auth', 'permission:admin.access', 'permission:staff.manage'])
    ->group(function () {
        Route::get('/display', [DisplayController::class, 'edit'])->name('display.edit');
        Route::put('/display', [DisplayController::class, 'update'])->name('display.update');
        Route::get('/ordering/{kind}', [OrderingController::class, 'edit'])
            ->whereIn('kind', ['members', 'roles'])
            ->name('ordering.edit');
        Route::put('/ordering/{kind}', [OrderingController::class, 'update'])
            ->whereIn('kind', ['members', 'roles'])
            ->name('ordering.update');

        Route::prefix('documents')
            ->name('documents.')
            ->group(function () {
                Route::get('/', [DocumentController::class, 'admin'])->name('index');
                Route::get('/create', [DocumentController::class, 'edit'])->name('create');
                Route::post('/', [DocumentController::class, 'save'])->name('store');
                Route::get('/{id}/revisions', [DocumentController::class, 'revisions'])
                    ->whereNumber('id')
                    ->name('revisions.index');
                Route::post('/{id}/revisions/{revision}/restore', [DocumentController::class, 'restore'])
                    ->whereNumber(['id', 'revision'])
                    ->name('revisions.restore');
                Route::get('/{id}/edit', [DocumentController::class, 'edit'])
                    ->whereNumber('id')
                    ->name('edit');
                Route::put('/{id}', [DocumentController::class, 'save'])
                    ->whereNumber('id')
                    ->name('update');
                Route::delete('/{id}', [DocumentController::class, 'destroy'])
                    ->whereNumber('id')
                    ->name('destroy');
            });

        Route::prefix('members')
            ->name('members.')
            ->group(function () {
                Route::get('/', [StaffController::class, 'admin'])->name('index');
                Route::get('/create', [StaffController::class, 'edit'])->name('create');
                Route::get('/{id}/edit', [StaffController::class, 'edit'])
                    ->whereNumber('id')
                    ->name('edit');
                Route::post('/', [StaffController::class, 'save'])->name('store');
                Route::put('/{id}', [StaffController::class, 'save'])
                    ->whereNumber('id')
                    ->name('update');
                Route::delete('/{id}', [StaffController::class, 'destroy'])
                    ->whereNumber('id')
                    ->name('destroy');
            });

        Route::prefix('roles')
            ->name('roles.')
            ->group(function () {
                Route::post('/', [StaffController::class, 'saveRole'])->name('store');
                Route::put('/{id}', [StaffController::class, 'saveRole'])
                    ->whereNumber('id')
                    ->name('update');
                Route::delete('/{id}', [StaffController::class, 'deleteRole'])
                    ->whereNumber('id')
                    ->name('destroy');
            });
    });
