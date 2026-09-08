<?php

declare(strict_types=1);

namespace Richness\RichTheme\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Richness\RichTheme\Conversion\HtmlThemeConverter;
use Richness\RichTheme\ThemeManager;
use Tests\TestCase;

final class HtmlThemeConverterTest extends TestCase
{
    use RefreshDatabase;

    private string $sourceDir;
    private string $themeDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceDir = storage_path('framework/testing/html-theme-source');
        $this->themeDir = resource_path('themes/converted-client');
        File::deleteDirectory($this->sourceDir);
        File::deleteDirectory($this->themeDir);
        File::ensureDirectoryExists($this->sourceDir.'/css');
        File::ensureDirectoryExists($this->sourceDir.'/js');
        File::ensureDirectoryExists($this->sourceDir.'/images');

        File::put($this->sourceDir.'/index.html', '<!doctype html><html><head><title>Home</title></head><body><header><a href="products.html">Products</a><img src="images/logo.svg"></header><main><section class="bg-slate-950 text-white"><h1>Home</h1></section><div data-rc-hook="storefront.home.digital-products"></div><section data-rc-feature="courses"><h2>Courses</h2></section></main><footer>Footer</footer><script src="js/app.js"></script></body></html>');
        File::put($this->sourceDir.'/products.html', '<!doctype html><html><head><title>Products</title></head><body><header>Other Header</header><main><h1 style="color:#f97316">Products</h1></main><footer>Other Footer</footer></body></html>');
        File::put($this->sourceDir.'/css/site.css', '.button{background:#f97316;color:#ffffff}.hero{background:url("../images/hero.png")}');
        File::put($this->sourceDir.'/js/app.js', 'window.themeLoaded = true;');
        File::put($this->sourceDir.'/images/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        File::put($this->sourceDir.'/images/hero.png', 'png');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->sourceDir);
        File::deleteDirectory($this->themeDir);

        parent::tearDown();
    }

    public function test_converter_generates_rich_theme_from_organized_html_directory(): void
    {
        $result = app(HtmlThemeConverter::class)->convert(
            source: $this->sourceDir,
            slug: 'converted-client',
            name: 'Converted Client',
            mode: 'dark',
            force: true,
        );

        $this->assertSame('converted-client', $result->slug);
        $this->assertFileExists($this->themeDir.'/theme.json');
        $this->assertFileExists($this->themeDir.'/views/layouts/storefront.blade.php');
        $this->assertFileExists($this->themeDir.'/views/home.blade.php');
        $this->assertFileExists($this->themeDir.'/views/products/index.blade.php');
        $this->assertFileExists($this->themeDir.'/views/partials/theme-header.blade.php');
        $this->assertFileExists($this->themeDir.'/views/partials/theme-footer.blade.php');
        $this->assertFileExists($this->themeDir.'/css/theme.css');
        $this->assertFileExists($this->themeDir.'/assets/js/app.js');
        $this->assertFileExists($this->themeDir.'/assets/images/logo.svg');

        $home = (string) File::get($this->themeDir.'/views/home.blade.php');
        $css = (string) File::get($this->themeDir.'/css/theme.css');
        $layout = (string) File::get($this->themeDir.'/views/layouts/storefront.blade.php');

        $this->assertStringContainsString("@extends('layouts.storefront')", $home);
        $this->assertStringContainsString('rc-bg-page', $home);
        $this->assertStringContainsString("route('rich-theme.asset'", $home);
        $this->assertStringContainsString("apply_filters('storefront.home.digital_products', '')", $home);
        $this->assertStringContainsString("StoreConfiguration::feature('courses')", $home);
        $this->assertStringContainsString('var(--rc-primary)', $css);
        $this->assertStringContainsString('/rich-theme-assets/converted-client/assets/images/hero.png', $css);
        $this->assertStringContainsString('assets/js/app.js', $layout);
        $this->assertStringContainsString("apply_filters('storefront.layout.before_content', '')", $layout);
    }

    public function test_generated_theme_can_be_loaded_by_theme_manager(): void
    {
        app(HtmlThemeConverter::class)->convert(
            source: $this->sourceDir,
            slug: 'converted-client',
            name: 'Converted Client',
            mode: 'dark',
            force: true,
        );

        $context = app(ThemeManager::class)->load('converted-client');

        $this->assertSame('Converted Client', $context->name);
        $this->assertTrue($context->hasView('home'));
    }

    public function test_artisan_command_generates_theme(): void
    {
        $this->artisan('theme:convert', [
            'source' => $this->sourceDir,
            'slug' => 'converted-client',
            '--name' => 'Converted Client',
            '--mode' => 'dark',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFileExists($this->themeDir.'/theme.json');
    }
}
