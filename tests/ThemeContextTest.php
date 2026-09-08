<?php

declare(strict_types=1);

namespace Richness\RichTheme\Tests;

use PHPUnit\Framework\TestCase;
use Richness\RichTheme\Support\DefaultTokens;
use Richness\RichTheme\ThemeContext;

final class ThemeContextTest extends TestCase
{
    private function createDefaultContext(): ThemeContext
    {
        $data = DefaultTokens::all();

        return new ThemeContext(
            name: 'Default',
            mode: 'dark',
            path: '/dummy/path',
            colors: [
                'primary' => '#f97316',
                'primary-hover' => '#ea580c',
                'accent' => '#f59e0b',
                'surface.page' => '#0f172a',
                'surface.card' => 'rgba(15, 23, 42, 0.9)',
                'text.heading' => '#ffffff',
                'text.body' => '#f1f5f9',
                'border.default' => 'rgba(255, 255, 255, 0.08)',
                'gradient.brand' => 'linear-gradient(135deg, var(--rc-primary) 0%, var(--rc-accent) 100%)',
            ],
            typography: $data['typography'],
            radius: $data['radius'],
            header: $data['header'],
            footer: $data['footer'],
            buttons: $data['buttons'],
            meta: [],
            raw: $data,
        );
    }

    public function test_accessors_return_correct_values(): void
    {
        $context = $this->createDefaultContext();

        $this->assertSame('#f97316', $context->color('primary'));
        $this->assertSame('#0f172a', $context->surface('page'));
        $this->assertSame('#ffffff', $context->text('heading'));
        $this->assertSame('rgba(255, 255, 255, 0.08)', $context->border('default'));
        $this->assertStringContainsString('linear-gradient', $context->gradient('brand'));
        $this->assertStringContainsString('Noto Sans Arabic', $context->font('body'));
        $this->assertNotNull($context->fontUrl());
        $this->assertSame('16px', $context->radius('lg'));
        $this->assertSame('#ffffff', $context->button('primary-text'));
        $this->assertTrue($context->isDark());
        $this->assertFalse($context->isLight());
        $this->assertTrue($context->has('colors.primary'));
    }

    public function test_to_css_variables_generates_root_block(): void
    {
        $context = $this->createDefaultContext();
        $css = $context->toCssVariables();

        $this->assertStringStartsWith(':root {', $css);
        $this->assertStringEndsWith('}', $css);
        $this->assertStringContainsString('--rc-primary: #f97316;', $css);
        $this->assertStringContainsString('--rc-surface-page: #0f172a;', $css);
        $this->assertStringContainsString('--rc-font-body:', $css);
        $this->assertStringContainsString('--rc-radius-md: 12px;', $css);
        $this->assertStringContainsString('--rc-header-height: 80px;', $css);
        $this->assertStringContainsString('--rc-btn-primary-text: #ffffff;', $css);
    }

    public function test_to_css_variables_map_returns_complete_array(): void
    {
        $context = $this->createDefaultContext();
        $map = $context->toCssVariablesMap();

        $this->assertArrayHasKey('--rc-primary', $map);
        $this->assertArrayHasKey('--rc-surface-page', $map);
        $this->assertArrayHasKey('--rc-font-body', $map);
        $this->assertSame('#f97316', $map['--rc-primary']);
    }

    public function test_json_and_array_serialization(): void
    {
        $context = $this->createDefaultContext();

        $this->assertIsArray($context->toArray());
        $this->assertSame('Default', $context->toArray()['name']);

        $json = $context->toJson();
        $this->assertJson($json);
        $this->assertStringContainsString('"Default"', $json);
    }
}
