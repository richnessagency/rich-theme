<?php

declare(strict_types=1);

namespace Richness\RichTheme\Conversion;

final class BladeTemplateCompiler
{
    public function __construct(
        private readonly CssTokenMapper $tokens,
        private readonly FeatureMarkerCompiler $markers,
    ) {}

    /**
     * @return array{body: string, header: ?string, footer: ?string, title: ?string}
     */
    public function extractParts(string $html): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $title = $this->firstText($dom, 'title');
        $header = $this->extractFirstTag($dom, 'header');
        $footer = $this->extractFirstTag($dom, 'footer');
        $body = $this->bodyHtml($dom);

        return [
            'body' => trim($this->markers->compile($this->tokens->mapHtml($body))),
            'header' => $header === null ? null : trim($this->markers->compile($this->tokens->mapHtml($header))),
            'footer' => $footer === null ? null : trim($this->markers->compile($this->tokens->mapHtml($footer))),
            'title' => $title,
        ];
    }

    /**
     * @param  list<string>  $scriptPaths
     */
    public function compileLayout(string $themeSlug, array $scriptPaths, bool $hasHeader, bool $hasFooter): string
    {
        $scripts = '';
        foreach ($scriptPaths as $path) {
            $scripts .= "\n    <script src=\"{{ route('rich-theme.asset', ['theme' => '{$themeSlug}', 'path' => '{$path}']) }}\" defer></script>";
        }

        $header = $hasHeader ? "\n    @include('partials.theme-header')" : '';
        $footer = $hasFooter ? "\n    @include('partials.theme-footer')" : '';

        return <<<BLADE
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo :seo="\$seo ?? null" />
    @include('rich-theme::components.theme-styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {!! apply_filters('storefront.layout.head', '') !!}
    @stack('styles')
</head>
<body class="rc-bg-page rc-text-body antialiased">
    {!! apply_filters('storefront.layout.body_start', '') !!}
    {!! apply_filters('storefront.announcement_bar', '') !!}
    {!! apply_filters('storefront.layout.before_header', '') !!}{$header}
    {!! apply_filters('storefront.layout.after_header', '') !!}
    <main>
        {!! apply_filters('storefront.layout.before_content', '') !!}
        @yield('content')
        {!! apply_filters('storefront.layout.after_content', '') !!}
    </main>
    {!! apply_filters('storefront.layout.before_footer', '') !!}{$footer}
    {!! apply_filters('storefront.layout.after_footer', '') !!}
    @stack('scripts'){$scripts}
    {!! apply_filters('storefront.layout.body_end', '') !!}
</body>
</html>
BLADE;
    }

    public function compilePage(string $content, ?string $title = null): string
    {
        $titleBlock = $title !== null && $title !== ''
            ? "\n@section('title', '".str_replace("'", "\\'", $title)."')\n"
            : "\n";

        return "@extends('layouts.storefront')".$titleBlock."\n@section('content')\n".$content."\n@endsection\n";
    }

    private function firstText(\DOMDocument $dom, string $tag): ?string
    {
        $nodes = $dom->getElementsByTagName($tag);
        if ($nodes->length === 0) {
            return null;
        }

        $text = trim((string) $nodes->item(0)?->textContent);

        return $text === '' ? null : $text;
    }

    private function extractFirstTag(\DOMDocument $dom, string $tag): ?string
    {
        $nodes = $dom->getElementsByTagName($tag);
        if ($nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);
        if (! $node instanceof \DOMNode) {
            return null;
        }

        $html = $dom->saveHTML($node) ?: null;
        $node->parentNode?->removeChild($node);

        return $html;
    }

    private function bodyHtml(\DOMDocument $dom): string
    {
        $bodies = $dom->getElementsByTagName('body');
        $node = $bodies->length > 0 ? $bodies->item(0) : $dom;
        if (! $node instanceof \DOMNode) {
            return '';
        }

        $html = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMNode) {
                $html .= $dom->saveHTML($child) ?: '';
            }
        }

        return $html;
    }
}
