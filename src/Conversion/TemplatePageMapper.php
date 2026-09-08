<?php

declare(strict_types=1);

namespace Richness\RichTheme\Conversion;

final class TemplatePageMapper
{
    /**
     * @return array{view: string, file: string}
     */
    public function map(string $htmlFile): array
    {
        $base = strtolower(pathinfo($htmlFile, PATHINFO_FILENAME));
        $normalized = str_replace(['_', ' '], '-', $base);

        return match ($normalized) {
            'index', 'home', 'homepage' => ['view' => 'home', 'file' => 'home.blade.php'],
            'products', 'product-index', 'shop', 'catalog' => ['view' => 'products.index', 'file' => 'products/index.blade.php'],
            'product', 'product-show', 'single-product' => ['view' => 'products.show', 'file' => 'products/show.blade.php'],
            'blog', 'posts', 'articles' => ['view' => 'blog.index', 'file' => 'blog/index.blade.php'],
            'article', 'post', 'blog-show', 'single-post', 'single-article' => ['view' => 'blog.show', 'file' => 'blog/show.blade.php'],
            'page', 'static-page' => ['view' => 'pages.show', 'file' => 'pages/show.blade.php'],
            default => ['view' => 'pages.'.$normalized, 'file' => 'pages/'.$normalized.'.blade.php'],
        };
    }
}

