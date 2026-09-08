<?php

declare(strict_types=1);

namespace Richness\RichTheme\Conversion;

final class CssTokenMapper
{
    /** @var array<string, string> */
    private const VALUE_MAP = [
        '#f97316' => 'var(--rc-primary)',
        '#ea580c' => 'var(--rc-primary-hover)',
        '#f59e0b' => 'var(--rc-accent)',
        '#d97706' => 'var(--rc-accent-hover)',
        '#0f172a' => 'var(--rc-surface-page)',
        '#020617' => 'var(--rc-surface-input)',
        '#1e293b' => 'var(--rc-btn-neutral-bg)',
        '#334155' => 'var(--rc-border-input)',
        '#ffffff' => 'var(--rc-text-heading)',
        '#fff' => 'var(--rc-text-heading)',
        '#f8fafc' => 'var(--rc-text-body)',
        '#f1f5f9' => 'var(--rc-text-body)',
        '#e2e8f0' => 'var(--rc-btn-neutral-text)',
        '#94a3b8' => 'var(--rc-text-muted)',
        '#64748b' => 'var(--rc-text-muted)',
        '#22c55e' => 'var(--rc-status-success)',
        '#16a34a' => 'var(--rc-status-success)',
        '#ef4444' => 'var(--rc-status-danger)',
        '#dc2626' => 'var(--rc-status-danger)',
        '#3b82f6' => 'var(--rc-status-info)',
        '#2563eb' => 'var(--rc-primary)',
    ];

    /** @var array<string, string> */
    private const CLASS_MAP = [
        'bg-slate-950' => 'rc-bg-page',
        'bg-slate-900' => 'rc-bg-card',
        'bg-slate-800' => 'rc-bg-card',
        'bg-orange-500' => 'rc-bg-primary',
        'text-orange-400' => 'rc-text-primary',
        'hover:text-orange-400' => 'hover:rc-text-primary',
        'text-slate-100' => 'rc-text-body',
        'text-slate-200' => 'rc-text-body',
        'text-slate-300' => 'rc-text-body',
        'text-slate-400' => 'rc-text-muted',
        'text-white' => 'rc-text-heading',
        'border-slate-800' => 'rc-border',
        'border-slate-700' => 'rc-border',
    ];

    public function mapHtml(string $html): string
    {
        return strtr($this->mapCssValues($html), self::CLASS_MAP);
    }

    public function mapCss(string $css, string $themeSlug): string
    {
        $css = $this->mapCssValues($css);

        return preg_replace_callback('/url\((["\']?)(?!data:|https?:|\/|#)([^"\')]+)\1\)/i', static function (array $matches) use ($themeSlug): string {
            $path = str_replace('\\', '/', $matches[2]);
            $path = preg_replace('#^(?:\./|\../)+#', '', $path) ?? $path;
            if (preg_match('#^(images|img|fonts|js)/#', $path) === 1) {
                $path = 'assets/'.$path;
            }

            return 'url("/rich-theme-assets/'.$themeSlug.'/'.$path.'")';
        }, $css) ?? $css;
    }

    private function mapCssValues(string $content): string
    {
        return str_ireplace(array_keys(self::VALUE_MAP), array_values(self::VALUE_MAP), $content);
    }
}
