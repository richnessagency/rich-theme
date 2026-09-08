<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Richness\RichTheme\Http\Controllers\ThemeController;

Route::prefix('admin/themes')
    ->middleware(['web', 'admin.placeholder'])
    ->group(function (): void {
        Route::get('/', [ThemeController::class, 'index'])->name('admin.themes.index');
        Route::post('/{name}/activate', [ThemeController::class, 'activate'])->name('admin.themes.activate');
        Route::get('/customize', [ThemeController::class, 'customize'])->name('admin.themes.customize');
        Route::post('/customize', [ThemeController::class, 'saveCustomization'])->name('admin.themes.save-customization');
        Route::post('/reset', [ThemeController::class, 'resetCustomization'])->name('admin.themes.reset');
    });
