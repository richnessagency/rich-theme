<?php

declare(strict_types=1);

namespace Richness\RichTheme;

use Illuminate\Support\Arr;

final readonly class ThemeContext
{
    /**
     * @param  array<string, string>  $colors  Flattened colors (e.g. ['primary' => '#f97316', 'surface.page' => '#0f172a'])
     * @param  array<string, mixed>  $typography
     * @param  array<string, string>  $radius
     * @param  array<string, string>  $header
     * @param  array<string, string>  $footer
     * @param  array<string, string>  $buttons
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $name,
        public string $mode,
        public string $path,
        public array $colors,
        public array $typography,
        public array $radius,
        public array $header,
        public array $footer,
        public array $buttons,
        public array $meta,
        public array $raw,
    ) {}

    public function color(string $key): string
    {
        return $this->colors[$key] ?? '';
    }

    public function surface(string $key): string
    {
        return $this->color('surface.'.$key);
    }

    public function text(string $key): string
    {
        return $this->color('text.'.$key);
    }

    public function border(string $key): string
    {
        return $this->color('border.'.$key);
    }

    public function gradient(string $key): string
    {
        return $this->color('gradient.'.$key);
    }

    public function font(string $key): string
    {
        return (string) ($this->typography['font-'.$key] ?? $this->typography[$key] ?? '');
    }

    public function fontUrl(): ?string
    {
        $url = $this->typography['font-url'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    public function radius(string $key): string
    {
        return $this->radius[$key] ?? '';
    }

    public function button(string $key): string
    {
        return $this->buttons[$key] ?? '';
    }

    public function isDark(): bool
    {
        return $this->mode === 'dark';
    }

    public function isLight(): bool
    {
        return $this->mode === 'light';
    }

    public function has(string $dotKey): bool
    {
        return Arr::has($this->raw, $dotKey);
    }

    /**
     * @return array<string, string>
     */
    public function toCssVariablesMap(): array
    {
        $map = [];

        // Colors
        foreach ($this->colors as $key => $value) {
            $varName = '--rc-'.str_replace('.', '-', (string) $key);
            $map[$varName] = (string) $value;
        }

        // Typography
        foreach ($this->typography as $key => $value) {
            if ($key !== 'font-url' && is_scalar($value)) {
                $cleanKey = str_starts_with($key, 'font-') ? $key : 'font-'.$key;
                $map['--rc-'.$cleanKey] = (string) $value;
            }
        }

        // Radius
        foreach ($this->radius as $key => $value) {
            $map['--rc-radius-'.$key] = (string) $value;
        }

        // Header
        foreach ($this->header as $key => $value) {
            $map['--rc-header-'.$key] = (string) $value;
        }

        // Footer
        foreach ($this->footer as $key => $value) {
            $map['--rc-footer-'.$key] = (string) $value;
        }

        // Buttons
        foreach ($this->buttons as $key => $value) {
            $map['--rc-btn-'.$key] = (string) $value;
        }

        return $map;
    }

    public function toCssVariables(): string
    {
        $map = $this->toCssVariablesMap();
        $lines = [':root {'];

        foreach ($map as $var => $value) {
            $lines[] = "    {$var}: {$value};";
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    public function toJson(): string
    {
        return (string) json_encode($this->raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    public function viewPath(): string
    {
        return $this->path.'/views';
    }

    public function hasView(string $view): bool
    {
        $normalized = str_replace('.', '/', $view);

        return file_exists($this->viewPath().'/'.$normalized.'.blade.php');
    }

    public function cssPath(): ?string
    {
        $path = $this->path.'/css/theme.css';

        return file_exists($path) ? $path : null;
    }

    public function assetPath(): ?string
    {
        $path = $this->path.'/assets';

        return is_dir($path) ? $path : null;
    }

    public function screenshotUrl(): ?string
    {
        $file = $this->path.'/screenshot.png';
        if (! file_exists($file)) {
            return null;
        }

        // If public asset exists, link it, otherwise data URL fallback for shared hosting
        if (str_starts_with($file, public_path())) {
            return asset(substr($file, strlen(public_path())));
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($content);
    }
}
