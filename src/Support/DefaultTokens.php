<?php

declare(strict_types=1);

namespace Richness\RichTheme\Support;

final class DefaultTokens
{
    /** @return array<string, mixed> Complete default theme data matching current dark design */
    public static function all(): array
    {
        return [
            'name' => 'Default',
            'version' => '1.0.0',
            'mode' => 'dark',
            'colors' => [
                'primary' => '#f97316',
                'primary-hover' => '#ea580c',
                'primary-light' => 'rgba(249, 115, 22, 0.12)',
                'primary-shadow' => 'rgba(249, 115, 22, 0.25)',
                'accent' => '#f59e0b',
                'accent-hover' => '#d97706',
                'surface' => [
                    'page' => '#0f172a',
                    'card' => 'rgba(15, 23, 42, 0.9)',
                    'raised' => 'rgba(15, 23, 42, 0.85)',
                    'input' => '#020617',
                    'overlay' => 'rgba(2, 6, 23, 0.74)',
                    'subcard' => 'rgba(2, 6, 23, 0.6)',
                ],
                'border' => [
                    'default' => 'rgba(255, 255, 255, 0.08)',
                    'hover' => 'rgba(255, 255, 255, 0.2)',
                    'input' => '#334155',
                ],
                'text' => [
                    'heading' => '#ffffff',
                    'body' => '#f1f5f9',
                    'muted' => '#94a3b8',
                    'inverse' => '#0f172a',
                ],
                'status' => [
                    'success' => '#22c55e',
                    'warning' => '#f59e0b',
                    'danger' => '#ef4444',
                    'info' => '#3b82f6',
                ],
                'gradient' => [
                    'brand' => 'linear-gradient(135deg, var(--rc-primary) 0%, var(--rc-accent) 100%)',
                    'brand-text' => 'linear-gradient(to right, var(--rc-primary), var(--rc-accent), #ffffff)',
                ],
            ],
            'typography' => [
                'font-body' => "'Noto Sans Arabic', ui-sans-serif, system-ui, sans-serif",
                'font-display' => "'Noto Kufi Arabic', 'Noto Sans Arabic', ui-sans-serif, system-ui, sans-serif",
                'font-url' => 'https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@500;700;800;900&family=Noto+Sans+Arabic:wght@400;500;600;700;800&display=swap',
            ],
            'radius' => [
                'sm' => '8px',
                'md' => '12px',
                'lg' => '16px',
                'xl' => '24px',
                'full' => '999px',
            ],
            'header' => [
                'height' => '80px',
                'bg' => 'rgba(15, 23, 42, 0.85)',
                'blur' => '12px',
                'border' => 'rgba(255, 255, 255, 0.08)',
            ],
            'footer' => [
                'bg' => '#0f172a',
                'border' => '#1e293b',
            ],
            'buttons' => [
                'primary-bg' => 'linear-gradient(135deg, var(--rc-primary), var(--rc-accent))',
                'primary-text' => '#ffffff',
                'primary-shadow' => '0 16px 34px rgba(249, 115, 22, 0.25)',
                'neutral-bg' => '#1e293b',
                'neutral-text' => '#e2e8f0',
                'neutral-border' => 'rgba(255, 255, 255, 0.08)',
            ],
            'meta' => [],
        ];
    }
}
