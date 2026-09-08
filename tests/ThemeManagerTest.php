<?php

declare(strict_types=1);

namespace Richness\RichTheme\Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Richness\RichTheme\ThemeContext;
use Richness\RichTheme\ThemeManager;
use Tests\TestCase;

final class ThemeManagerTest extends TestCase
{
    use RefreshDatabase;

    private ThemeManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::flushCache();
        $this->manager = app(ThemeManager::class);
    }

    public function test_active_theme_name_defaults_to_config_or_default(): void
    {
        $this->assertSame('default', $this->manager->activeThemeName());

        Setting::setValue('active_theme', 'light-clean');
        $this->assertSame('light-clean', $this->manager->activeThemeName());
    }

    public function test_resolve_returns_theme_context(): void
    {
        $context = $this->manager->resolve();

        $this->assertInstanceOf(ThemeContext::class, $context);
        $this->assertSame('Default', $context->name);
        $this->assertNotEmpty($context->color('primary'));
        $this->assertTrue($context->isDark());
    }

    public function test_save_customization_and_reset_customization(): void
    {
        $this->manager->saveCustomization([
            'primary' => '#123456',
            'surface_card' => 'rgba(10, 20, 30, 0.8)',
            'radius_lg' => '20px',
            'font_body' => "'Tajawal', ui-sans-serif, system-ui, sans-serif",
        ]);

        $this->assertSame('#123456', Setting::getValue('theme_primary'));
        $this->assertSame('rgba(10, 20, 30, 0.8)', Setting::getValue('theme_surface_card'));
        $this->assertSame('20px', Setting::getValue('theme_radius_lg'));
        $this->assertSame('#123456', $this->manager->resolve()->color('primary'));
        $this->assertSame('rgba(10, 20, 30, 0.8)', $this->manager->resolve()->surface('card'));
        $this->assertSame('20px', $this->manager->resolve()->radius('lg'));
        $this->assertStringContainsString('Tajawal', $this->manager->resolve()->font('body'));

        $this->manager->resetCustomization();
        $this->assertNull(Setting::getValue('theme_primary'));
        $this->assertNull(Setting::getValue('theme_surface_card'));
        $this->assertNotSame('#123456', $this->manager->resolve()->color('primary'));
    }

    public function test_save_customization_rejects_unknown_or_unsafe_values(): void
    {
        $this->manager->saveCustomization([
            'primary' => '#abcdef',
            'surface_card' => 'url(javascript:alert(1))',
            'radius_lg' => 'calc(100vh + 1px)',
            'font_body' => "Arial; body { display:none }",
            'unknown_key' => '#111111',
        ]);

        $this->assertSame('#abcdef', Setting::getValue('theme_primary'));
        $this->assertNull(Setting::getValue('theme_surface_card'));
        $this->assertNull(Setting::getValue('theme_radius_lg'));
        $this->assertNull(Setting::getValue('theme_font_body'));
        $this->assertNull(Setting::getValue('theme_unknown_key'));
    }

    public function test_validate_detects_non_existent_theme(): void
    {
        $result = $this->manager->validate('non-existent-theme-12345');
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_available_themes_includes_default(): void
    {
        $themes = $this->manager->availableThemes();

        $this->assertArrayHasKey('default', $themes);
        $this->assertSame('Default', $themes['default']['name']);
    }

    public function test_register_view_overrides_prepends_path_if_directory_exists(): void
    {
        $themeDir = resource_path('themes/test-override-theme');
        $viewDir = $themeDir.'/views';
        mkdir($viewDir, 0777, true);
        file_put_contents($themeDir.'/theme.json', json_encode(['name' => 'Test Override', 'version' => '1.0.0', 'mode' => 'dark', 'colors' => []]));

        try {
            $context = $this->manager->load('test-override-theme');
            $this->manager->registerViewOverrides($context);
            $paths = app('view')->getFinder()->getPaths();
            $this->assertSame($viewDir, $paths[0]);
        } finally {
            @unlink($themeDir.'/theme.json');
            @rmdir($viewDir);
            @rmdir($themeDir);
        }
    }
}
