<?php

declare(strict_types=1);

namespace Richness\RichTheme\Conversion;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Richness\RichTheme\Support\DefaultTokens;

final class HtmlThemeConverter
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly Filesystem $files,
        private readonly BladeTemplateCompiler $blade,
        private readonly CssTokenMapper $tokens,
        private readonly TemplatePageMapper $pages,
    ) {}

    public function convert(string $source, ?string $slug = null, ?string $name = null, string $mode = 'auto', bool $force = false): ThemeConversionResult
    {
        $sourcePath = realpath($source);
        if ($sourcePath === false) {
            throw new \InvalidArgumentException("Source path [{$source}] does not exist.");
        }

        $slug = $this->normalizeSlug($slug ?: pathinfo($sourcePath, PATHINFO_FILENAME));
        $themePath = rtrim((string) $this->config->get('theme.path', resource_path('themes')), '/').'/'.$slug;

        if ($this->files->exists($themePath) && ! $force) {
            throw new \RuntimeException("Theme [{$slug}] already exists. Use --force to overwrite it.");
        }

        $htmlFiles = $this->discoverHtmlFiles($sourcePath);
        if ($htmlFiles === []) {
            throw new \RuntimeException('No HTML files found. Add index.html, products.html, blog.html, article.html, or similar files.');
        }

        if ($force && $this->files->isDirectory($themePath)) {
            $this->files->deleteDirectory($themePath);
        }

        $this->files->ensureDirectoryExists($themePath.'/views/layouts');
        $this->files->ensureDirectoryExists($themePath.'/views/partials');
        $this->files->ensureDirectoryExists($themePath.'/css');
        $this->files->ensureDirectoryExists($themePath.'/assets');

        $baseDir = is_dir($sourcePath) ? $sourcePath : dirname($sourcePath);
        $scriptPaths = $this->copyStaticDirectories($baseDir, $themePath, $slug);
        $this->writeThemeCss($baseDir, $themePath, $slug);

        $header = null;
        $footer = null;
        $writtenPages = [];

        foreach ($htmlFiles as $htmlFile) {
            $parts = $this->blade->extractParts((string) $this->files->get($htmlFile));
            $header ??= $parts['header'];
            $footer ??= $parts['footer'];

            $mapped = $this->pages->map($htmlFile);
            $target = $themePath.'/views/'.$mapped['file'];
            $this->files->ensureDirectoryExists(dirname($target));
            $this->files->put($target, $this->blade->compilePage($this->rewriteAssetReferences($parts['body'], $slug), $parts['title']));
            $writtenPages[] = $mapped['view'];
        }

        if ($header !== null) {
            $this->files->put($themePath.'/views/partials/theme-header.blade.php', $this->rewriteAssetReferences($header, $slug)."\n");
        }

        if ($footer !== null) {
            $this->files->put($themePath.'/views/partials/theme-footer.blade.php', $this->rewriteAssetReferences($footer, $slug)."\n");
        }

        $detectedMode = $mode === 'auto' ? $this->detectMode($htmlFiles) : $mode;
        $this->files->put($themePath.'/views/layouts/storefront.blade.php', $this->blade->compileLayout($slug, $scriptPaths, $header !== null, $footer !== null)."\n");
        $this->files->put($themePath.'/theme.json', $this->themeJson($name ?: Str::headline($slug), $detectedMode));
        $this->files->put($themePath.'/README.md', $this->themeReadme($slug, $writtenPages));

        return new ThemeConversionResult($slug, $themePath, $writtenPages);
    }

    /**
     * @return list<string>
     */
    private function discoverHtmlFiles(string $source): array
    {
        if (is_file($source)) {
            return [$source];
        }

        $files = array_values(array_filter($this->files->files($source), static fn (\SplFileInfo $file): bool => in_array(strtolower($file->getExtension()), ['html', 'htm'], true)));

        usort($files, static function (\SplFileInfo $a, \SplFileInfo $b): int {
            $weight = static fn (string $name): int => match (strtolower(pathinfo($name, PATHINFO_FILENAME))) {
                'index', 'home', 'homepage' => 0,
                default => 10,
            };

            return [$weight($a->getFilename()), $a->getFilename()] <=> [$weight($b->getFilename()), $b->getFilename()];
        });

        return array_map(static fn (\SplFileInfo $file): string => $file->getPathname(), $files);
    }

    /**
     * @return list<string>
     */
    private function copyStaticDirectories(string $sourceDir, string $themePath, string $slug): array
    {
        $scriptPaths = [];

        foreach (['assets', 'images', 'img', 'fonts'] as $dir) {
            if ($this->files->isDirectory($sourceDir.'/'.$dir)) {
                $this->files->copyDirectory($sourceDir.'/'.$dir, $themePath.'/assets/'.$dir);
            }
        }

        if ($this->files->isDirectory($sourceDir.'/js')) {
            $this->files->copyDirectory($sourceDir.'/js', $themePath.'/assets/js');

            foreach ($this->files->allFiles($themePath.'/assets/js') as $file) {
                if (strtolower($file->getExtension()) === 'js') {
                    $scriptPaths[] = 'assets/js/'.str_replace('\\', '/', $file->getRelativePathname());
                }
            }
        }

        sort($scriptPaths);

        return $scriptPaths;
    }

    private function writeThemeCss(string $sourceDir, string $themePath, string $slug): void
    {
        $chunks = ["/* Generated by php artisan theme:convert. Use var(--rc-*) tokens for future edits. */\n"];

        if ($this->files->isDirectory($sourceDir.'/css')) {
            foreach ($this->files->allFiles($sourceDir.'/css') as $file) {
                if (strtolower($file->getExtension()) !== 'css') {
                    continue;
                }

                $relative = str_replace('\\', '/', $file->getRelativePathname());
                $chunks[] = "\n/* Source: css/{$relative} */\n".$this->tokens->mapCss((string) $this->files->get($file->getPathname()), $slug);
            }
        }

        $this->files->put($themePath.'/css/theme.css', trim(implode("\n", $chunks))."\n");
    }

    private function rewriteAssetReferences(string $html, string $slug): string
    {
        return preg_replace_callback('/\b(src|href)=([\'"])(?!https?:|data:|#|mailto:|tel:|\/|{{)([^\'"]+)\2/i', static function (array $matches) use ($slug): string {
            $path = ltrim(str_replace('\\', '/', $matches[3]), './');
            $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));

            if (! in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'ico', 'avif', 'woff', 'woff2', 'ttf', 'otf'], true)) {
                return $matches[0];
            }

            $path = preg_replace('#^(?:\./|\../)+#', '', $path) ?? $path;
            if (preg_match('#^(images|img|fonts|js)/#', $path) === 1) {
                $path = 'assets/'.$path;
            }

            return $matches[1].'='.$matches[2]."{{ route('rich-theme.asset', ['theme' => '{$slug}', 'path' => '{$path}']) }}".$matches[2];
        }, $html) ?? $html;
    }

    /**
     * @param  list<string>  $htmlFiles
     */
    private function detectMode(array $htmlFiles): string
    {
        $content = strtolower(implode("\n", array_map(fn (string $file): string => (string) $this->files->get($file), $htmlFiles)));

        if (preg_match('/(?:background(?:-color)?\s*:\s*|bg-)(#0f172a|#020617|slate-9|black)/', $content) === 1) {
            return 'dark';
        }

        return 'light';
    }

    private function themeJson(string $name, string $mode): string
    {
        $tokens = DefaultTokens::all();
        $tokens['name'] = $name;
        $tokens['mode'] = $mode;
        $tokens['meta'] = [
            'author' => 'RichTheme Converter',
            'description' => 'Converted HTML theme for RichCommerce',
            'tags' => ['converted', 'html'],
        ];

        if ($mode === 'light') {
            $tokens = array_replace_recursive($tokens, [
                'colors' => [
                    'primary' => '#2563eb',
                    'primary-hover' => '#1d4ed8',
                    'accent' => '#0f172a',
                    'surface' => ['page' => '#f8fafc', 'card' => '#ffffff', 'raised' => 'rgba(255, 255, 255, 0.95)', 'input' => '#ffffff'],
                    'border' => ['default' => '#e2e8f0', 'hover' => '#cbd5e1', 'input' => '#cbd5e1'],
                    'text' => ['heading' => '#0f172a', 'body' => '#1e293b', 'muted' => '#64748b', 'inverse' => '#ffffff'],
                ],
                'header' => ['bg' => 'rgba(255, 255, 255, 0.95)', 'border' => '#e2e8f0'],
                'buttons' => ['primary-bg' => 'var(--rc-primary)', 'primary-shadow' => '0 8px 20px rgba(37, 99, 235, 0.2)'],
            ]);
        }

        return (string) json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    }

    /**
     * @param  list<string>  $pages
     */
    private function themeReadme(string $slug, array $pages): string
    {
        return "# {$slug}\n\nGenerated with `php artisan theme:convert`.\n\nPages:\n\n- ".implode("\n- ", $pages)."\n\nEdit `theme.json` for tokens, `css/theme.css` for trusted developer CSS, and `views/` for Blade overrides.\n";
    }

    private function normalizeSlug(string $slug): string
    {
        $normalized = Str::slug($slug);
        if ($normalized === '') {
            throw new \InvalidArgumentException('Theme slug cannot be empty.');
        }

        return $normalized;
    }
}
