<?php

declare(strict_types=1);

namespace Richness\RichTheme\Tests;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ThemeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): self
    {
        $staff = User::factory()->create([
            'type' => 'staff',
            'active' => true,
        ]);

        return $this->actingAs($staff)->withSession(['admin_authenticated' => true]);
    }

    public function test_index_page_loads_successfully(): void
    {
        $response = $this->actingAsAdmin()->get(route('admin.themes.index'));

        $response->assertOk();
        $response->assertViewIs('rich-theme::admin.themes.index');
        $response->assertSee('معرض الثيمات والمظهر');
        $response->assertSee('Default');
    }

    public function test_customize_page_loads_successfully(): void
    {
        $response = $this->actingAsAdmin()->get(route('admin.themes.customize'));

        $response->assertOk();
        $response->assertViewIs('rich-theme::admin.themes.customize');
        $response->assertSee('تخصيص الثيم');
    }

    public function test_activate_theme_switches_active_setting(): void
    {
        $response = $this->actingAsAdmin()->post(route('admin.themes.activate', ['name' => 'light-clean']));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');
        $this->assertSame('light-clean', Setting::getValue('active_theme'));
    }

    public function test_activate_invalid_theme_returns_error(): void
    {
        $response = $this->actingAsAdmin()->post(route('admin.themes.activate', ['name' => 'non-existent-theme-xyz']));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('error');
    }

    public function test_save_customization_updates_settings(): void
    {
        $response = $this->actingAsAdmin()->post(route('admin.themes.save-customization'), [
            'primary' => '#ff5500',
            'accent' => '#0055ff',
            'surface_card' => 'rgba(10, 20, 30, 0.8)',
            'radius_lg' => '20px',
            'font_body' => "'Tajawal', ui-sans-serif, system-ui, sans-serif",
        ]);

        $response->assertRedirect(route('admin.themes.customize'));
        $response->assertSessionHas('success');
        $this->assertSame('#ff5500', Setting::getValue('theme_primary'));
        $this->assertSame('#0055ff', Setting::getValue('theme_accent'));
        $this->assertSame('rgba(10, 20, 30, 0.8)', Setting::getValue('theme_surface_card'));
        $this->assertSame('20px', Setting::getValue('theme_radius_lg'));
        $this->assertStringContainsString('Tajawal', (string) Setting::getValue('theme_font_body'));
    }

    public function test_save_customization_rejects_unsafe_css_values(): void
    {
        $response = $this->actingAsAdmin()->from(route('admin.themes.customize'))->post(route('admin.themes.save-customization'), [
            'primary' => 'url(javascript:alert(1))',
            'radius_lg' => 'calc(100vh + 1px)',
            'font_body' => 'Arial; body { display:none }',
        ]);

        $response->assertRedirect(route('admin.themes.customize'));
        $response->assertSessionHasErrors(['primary', 'radius_lg', 'font_body']);
        $this->assertNull(Setting::getValue('theme_primary'));
        $this->assertNull(Setting::getValue('theme_radius_lg'));
        $this->assertNull(Setting::getValue('theme_font_body'));
    }

    public function test_reset_customization_removes_overrides(): void
    {
        Setting::setValue('theme_primary', '#ff0000');
        Setting::setValue('theme_accent', '#00ff00');

        $response = $this->actingAsAdmin()->post(route('admin.themes.reset'));

        $response->assertRedirect(route('admin.themes.customize'));
        $response->assertSessionHas('success');
        $this->assertNull(Setting::getValue('theme_primary'));
        $this->assertNull(Setting::getValue('theme_accent'));
    }
}
