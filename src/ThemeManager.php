<?php

declare(strict_types=1);

namespace Richness\RichTheme;

use App\Models\Setting;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Richness\RichTheme\Support\DefaultTokens;

final class ThemeManager
{
    /**
     * @var array<string, array{path: list<string>, type: string, label: string, section: string}>
     */
    private const CUSTOMIZATION_MAP = [
        'primary' => ['path' => ['colors', 'primary'], 'type' => 'color', 'label' => 'اللون الأساسي', 'section' => 'brand'],
        'primary_hover' => ['path' => ['colors', 'primary-hover'], 'type' => 'color', 'label' => 'اللون الأساسي عند المرور', 'section' => 'brand'],
        'accent' => ['path' => ['colors', 'accent'], 'type' => 'color', 'label' => 'اللون الثانوي', 'section' => 'brand'],
        'accent_hover' => ['path' => ['colors', 'accent-hover'], 'type' => 'color', 'label' => 'اللون الثانوي عند المرور', 'section' => 'brand'],
        'surface_page' => ['path' => ['colors', 'surface', 'page'], 'type' => 'color', 'label' => 'خلفية الصفحة', 'section' => 'surfaces'],
        'surface_card' => ['path' => ['colors', 'surface', 'card'], 'type' => 'color', 'label' => 'خلفية البطاقات', 'section' => 'surfaces'],
        'surface_input' => ['path' => ['colors', 'surface', 'input'], 'type' => 'color', 'label' => 'خلفية الحقول', 'section' => 'surfaces'],
        'border_default' => ['path' => ['colors', 'border', 'default'], 'type' => 'color', 'label' => 'لون الحدود', 'section' => 'surfaces'],
        'border_input' => ['path' => ['colors', 'border', 'input'], 'type' => 'color', 'label' => 'حدود الحقول', 'section' => 'surfaces'],
        'text_heading' => ['path' => ['colors', 'text', 'heading'], 'type' => 'color', 'label' => 'لون العناوين', 'section' => 'text'],
        'text_body' => ['path' => ['colors', 'text', 'body'], 'type' => 'color', 'label' => 'لون النصوص', 'section' => 'text'],
        'text_muted' => ['path' => ['colors', 'text', 'muted'], 'type' => 'color', 'label' => 'لون النصوص الثانوية', 'section' => 'text'],
        'font_body' => ['path' => ['typography', 'font-body'], 'type' => 'font', 'label' => 'خط النصوص', 'section' => 'typography'],
        'font_display' => ['path' => ['typography', 'font-display'], 'type' => 'font', 'label' => 'خط العناوين', 'section' => 'typography'],
        'radius_sm' => ['path' => ['radius', 'sm'], 'type' => 'length', 'label' => 'استدارة صغيرة', 'section' => 'radius'],
        'radius_md' => ['path' => ['radius', 'md'], 'type' => 'length', 'label' => 'استدارة متوسطة', 'section' => 'radius'],
        'radius_lg' => ['path' => ['radius', 'lg'], 'type' => 'length', 'label' => 'استدارة كبيرة', 'section' => 'radius'],
        'radius_xl' => ['path' => ['radius', 'xl'], 'type' => 'length', 'label' => 'استدارة كبيرة جداً', 'section' => 'radius'],
        'header_height' => ['path' => ['header', 'height'], 'type' => 'length', 'label' => 'ارتفاع الهيدر', 'section' => 'layout'],
        'header_bg' => ['path' => ['header', 'bg'], 'type' => 'color', 'label' => 'خلفية الهيدر', 'section' => 'layout'],
        'footer_bg' => ['path' => ['footer', 'bg'], 'type' => 'color', 'label' => 'خلفية الفوتر', 'section' => 'layout'],
        'footer_border' => ['path' => ['footer', 'border'], 'type' => 'color', 'label' => 'حد الفوتر', 'section' => 'layout'],
    ];

    private ?ThemeContext $resolved = null;

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly Filesystem $files,
    ) {}

    public function resolve(): ThemeContext
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $activeName = $this->activeThemeName();
        $themeData = $this->loadThemeJson($activeName);
        $themeData = $this->mergeWithDefaults($themeData);

        if ((bool) $this->config->get('theme.allow_db_overrides', true)) {
            $themeData = $this->applyDbOverrides($themeData);
        }

        $this->resolved = $this->buildContext($activeName, $themeData);

        return $this->resolved;
    }

    public function load(string $name): ThemeContext
    {
        $themeData = $this->loadThemeJson($name);
        $themeData = $this->mergeWithDefaults($themeData);

        return $this->buildContext($name, $themeData);
    }

    /**
     * @return array<string, array{name: string, description: string, mode: string, screenshot: ?string, has_views: bool}>
     */
    public function availableThemes(): array
    {
        $basePath = (string) $this->config->get('theme.path', resource_path('themes'));
        $themes = [];

        if ($this->files->isDirectory($basePath)) {
            $directories = $this->files->directories($basePath);
            foreach ($directories as $dir) {
                $name = basename($dir);
                $jsonPath = $dir.'/theme.json';
                if ($this->files->exists($jsonPath)) {
                    $content = json_decode((string) $this->files->get($jsonPath), true);
                    if (is_array($content)) {
                        $themes[$name] = [
                            'name' => (string) ($content['name'] ?? $name),
                            'description' => (string) ($content['meta']['description'] ?? ''),
                            'mode' => (string) ($content['mode'] ?? 'dark'),
                            'screenshot' => $this->resolveScreenshotUrl($dir),
                            'has_views' => $this->files->isDirectory($dir.'/views'),
                        ];
                    }
                }
            }
        }

        // Always ensure 'default' is listed
        if (! isset($themes['default'])) {
            $default = DefaultTokens::all();
            $themes['default'] = [
                'name' => (string) $default['name'],
                'description' => 'Default dark theme for RichCommerce',
                'mode' => (string) $default['mode'],
                'screenshot' => null,
                'has_views' => false,
            ];
        }

        return $themes;
    }

    public function activeThemeName(): string
    {
        try {
            return (string) Setting::getValue(
                'active_theme',
                (string) $this->config->get('theme.active', 'default')
            );
        } catch (\Throwable) {
            return (string) $this->config->get('theme.active', 'default');
        }
    }

    public function forgetResolved(): void
    {
        $this->resolved = null;
    }

    public function activate(string $name): void
    {
        $validation = $this->validate($name);
        if (! $validation['valid']) {
            throw new \InvalidArgumentException('Invalid theme: '.implode(', ', $validation['errors']));
        }

        Setting::setValue('active_theme', $name);
        $this->forgetResolved();
    }

    public function registerViewOverrides(ThemeContext $context): void
    {
        $viewPath = $context->viewPath();
        if (is_dir($viewPath)) {
            $finder = app('view')->getFinder();
            if (method_exists($finder, 'prependLocation')) {
                $finder->prependLocation($viewPath);
            }
        }
    }

    /**
     * @return array{valid: bool, errors: list<string>}
     */
    public function validate(string $name): array
    {
        $errors = [];
        $basePath = $this->resolveThemePath($name);

        if (! $this->files->isDirectory($basePath)) {
            if ($name === 'default') {
                return ['valid' => true, 'errors' => []];
            }

            return ['valid' => false, 'errors' => ["Theme directory [{$name}] does not exist."]];
        }

        $jsonFile = $basePath.'/theme.json';
        if (! $this->files->exists($jsonFile)) {
            return ['valid' => false, 'errors' => ["Theme file [theme.json] is missing in theme [{$name}]."]];
        }

        $data = json_decode((string) $this->files->get($jsonFile), true);
        if (! is_array($data)) {
            return ['valid' => false, 'errors' => ["Invalid JSON in theme [{$name}] theme.json."]];
        }

        $required = ['name', 'version', 'colors', 'mode'];
        foreach ($required as $field) {
            if (! array_key_exists($field, $data)) {
                $errors[] = "Missing required field [{$field}].";
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function compileCss(ThemeContext $context): string
    {
        $css = $context->toCssVariables();
        $customCssPath = $context->cssPath();

        if ($customCssPath !== null && file_exists($customCssPath)) {
            $customContent = file_get_contents($customCssPath);
            if ($customContent !== false) {
                $css .= "\n\n/* Custom Theme Styles */\n".$customContent;
            }
        }

        return $css;
    }

    /**
     * @return array<string, array{path: list<string>, type: string, label: string, section: string, value: string}>
     */
    public function customizableTokens(?ThemeContext $context = null): array
    {
        $context ??= $this->resolve();
        $tokens = [];

        foreach (self::CUSTOMIZATION_MAP as $key => $definition) {
            $tokens[$key] = $definition + [
                'value' => (string) data_get($context->raw, implode('.', $definition['path']), ''),
            ];
        }

        return $tokens;
    }

    /**
     * @param  array<string, string|null>  $tokens
     */
    public function saveCustomization(array $tokens): void
    {
        foreach ($tokens as $key => $value) {
            $sanitized = $this->sanitizeCustomizationValue((string) $key, $value);

            if ($sanitized !== null) {
                Setting::setValue('theme_'.$key, $sanitized);
            }
        }

        $this->forgetResolved();
    }

    public function resetCustomization(): void
    {
        Setting::query()->where('key', 'like', 'theme_%')->delete();
        Setting::flushCache();

        $this->forgetResolved();
    }

    // Facade proxy methods
    public function color(string $key): string
    {
        return $this->resolve()->color($key);
    }

    public function surface(string $key): string
    {
        return $this->resolve()->surface($key);
    }

    public function text(string $key): string
    {
        return $this->resolve()->text($key);
    }

    public function gradient(string $key): string
    {
        return $this->resolve()->gradient($key);
    }

    public function font(string $key): string
    {
        return $this->resolve()->font($key);
    }

    public function css(): string
    {
        return $this->resolve()->toCssVariables();
    }

    public function isDark(): bool
    {
        return $this->resolve()->isDark();
    }

    public function isLight(): bool
    {
        return $this->resolve()->isLight();
    }

    public function context(): ThemeContext
    {
        return $this->resolve();
    }

    // Private helpers
    /**
     * @return array<string, mixed>
     */
    private function loadThemeJson(string $name): array
    {
        $path = $this->resolveThemePath($name).'/theme.json';
        if ($this->files->exists($path)) {
            $decoded = json_decode((string) $this->files->get($path), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if ($name !== 'default') {
            $defaultPath = $this->resolveThemePath('default').'/theme.json';
            if ($this->files->exists($defaultPath)) {
                $decoded = json_decode((string) $this->files->get($defaultPath), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return DefaultTokens::all();
    }

    /**
     * @param  array<string, mixed>  $themeData
     * @return array<string, mixed>
     */
    private function applyDbOverrides(array $themeData): array
    {
        try {
            foreach (self::CUSTOMIZATION_MAP as $dbKey => $definition) {
                $val = Setting::getValue('theme_'.$dbKey);
                if ($val !== null && $val !== '') {
                    data_set($themeData, implode('.', $definition['path']), $val);
                }
            }
        } catch (\Throwable) {
            // fallback
        }

        return $themeData;
    }

    /**
     * @param  array<string, mixed>  $themeData
     */
    private function buildContext(string $name, array $themeData): ThemeContext
    {
        $colors = $this->flattenTokens($themeData['colors'] ?? []);
        $typography = is_array($themeData['typography'] ?? null) ? $themeData['typography'] : [];
        $radius = is_array($themeData['radius'] ?? null) ? $themeData['radius'] : [];
        $header = is_array($themeData['header'] ?? null) ? $themeData['header'] : [];
        $footer = is_array($themeData['footer'] ?? null) ? $themeData['footer'] : [];
        $buttons = is_array($themeData['buttons'] ?? null) ? $themeData['buttons'] : [];
        $meta = is_array($themeData['meta'] ?? null) ? $themeData['meta'] : [];

        return new ThemeContext(
            name: (string) ($themeData['name'] ?? $name),
            mode: (string) ($themeData['mode'] ?? 'dark'),
            path: $this->resolveThemePath($name),
            colors: $colors,
            typography: $typography,
            radius: $radius,
            header: $header,
            footer: $footer,
            buttons: $buttons,
            meta: $meta,
            raw: $themeData,
        );
    }

    /**
     * @param  array<string, mixed>  $nested
     * @return array<string, string>
     */
    private function flattenTokens(array $nested, string $prefix = ''): array
    {
        $flat = [];

        foreach ($nested as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $flat = array_merge($flat, $this->flattenTokens($value, $fullKey));
            } elseif (is_scalar($value)) {
                $flat[$fullKey] = (string) $value;
            }
        }

        return $flat;
    }

    private function resolveThemePath(string $name): string
    {
        $base = (string) $this->config->get('theme.path', resource_path('themes'));

        return rtrim($base, '/').'/'.$name;
    }

    /**
     * @param  array<string, mixed>  $themeData
     * @return array<string, mixed>
     */
    private function mergeWithDefaults(array $themeData): array
    {
        $defaults = DefaultTokens::all();

        return array_replace_recursive($defaults, $themeData);
    }

    private function resolveScreenshotUrl(string $dir): ?string
    {
        $file = $dir.'/screenshot.png';
        if (! $this->files->exists($file)) {
            return null;
        }

        $content = $this->files->get($file);

        return 'data:image/png;base64,'.base64_encode($content);
    }

    private function sanitizeCustomizationValue(string $key, mixed $value): ?string
    {
        if (! isset(self::CUSTOMIZATION_MAP[$key]) || ! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return match (self::CUSTOMIZATION_MAP[$key]['type']) {
            'color' => preg_match('/^(?:#[0-9a-fA-F]{6}|rgba?\(\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\))$/D', $value) === 1 ? strtolower($value) : null,
            'length' => preg_match('/^(?:0|[0-9]{1,3}(?:\.[0-9]{1,2})?(?:px|rem|em|%|vh|vw))$/D', $value) === 1 ? $value : null,
            'font' => preg_match('/[;{}<>]/', $value) === 1 || mb_strlen($value) > 220 ? null : $value,
            default => null,
        };
    }
}
