<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Richness\RichTheme\Http\Controllers\ThemeAssetController;

Route::get('/rich-theme-assets/{theme}/{path}', ThemeAssetController::class)
    ->where('path', '.*')
    ->name('rich-theme.asset');

