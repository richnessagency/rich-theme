<?php

declare(strict_types=1);

namespace Richness\RichTheme\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class ThemeAssetControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $themeDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeDir = resource_path('themes/asset-test');
        File::deleteDirectory($this->themeDir);
        File::ensureDirectoryExists($this->themeDir.'/assets/js');
        File::put($this->themeDir.'/assets/js/app.js', 'window.assetTest = true;');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->themeDir);

        parent::tearDown();
    }

    public function test_theme_assets_are_served_from_theme_directory(): void
    {
        $response = $this->get(route('rich-theme.asset', ['theme' => 'asset-test', 'path' => 'assets/js/app.js']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $response->assertSee('window.assetTest = true;', false);
    }

    public function test_theme_asset_route_rejects_traversal(): void
    {
        $this->get('/rich-theme-assets/asset-test/../default/theme.json')->assertNotFound();
    }
}
