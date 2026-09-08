<?php

declare(strict_types=1);

namespace Richness\RichTheme\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class ThemeAssetController extends Controller
{
    public function __invoke(string $theme, string $path): Response
    {
        if (preg_match('/^[a-z0-9][a-z0-9-]*$/', $theme) !== 1) {
            abort(404);
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if (str_contains($relative, '..') || str_starts_with($relative, '.')) {
            abort(404);
        }

        $base = realpath(resource_path('themes/'.$theme));
        $file = realpath(resource_path('themes/'.$theme.'/'.$relative));

        if ($base === false || $file === false || ! str_starts_with($file, $base.DIRECTORY_SEPARATOR) || ! is_file($file)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $allowed = [
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'avif' => 'image/avif',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
        ];

        if (! isset($allowed[$extension])) {
            abort(404);
        }

        return response((string) file_get_contents($file), 200, [
            'Content-Type' => $allowed[$extension],
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}

