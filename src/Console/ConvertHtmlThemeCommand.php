<?php

declare(strict_types=1);

namespace Richness\RichTheme\Console;

use Illuminate\Console\Command;
use Richness\RichTheme\Conversion\HtmlThemeConverter;
use Richness\RichTheme\ThemeManager;

final class ConvertHtmlThemeCommand extends Command
{
    protected $signature = 'theme:convert
        {source : HTML file or directory containing organized template files}
        {slug? : Theme slug, e.g. client-modern}
        {--name= : Human readable theme name}
        {--mode=auto : auto, dark, or light}
        {--force : Overwrite an existing generated theme}
        {--activate : Activate the converted theme after generation}';

    protected $description = 'Convert organized HTML/CSS/JS templates into a RichTheme-compatible theme.';

    public function handle(HtmlThemeConverter $converter, ThemeManager $themes): int
    {
        $source = (string) $this->argument('source');
        $slug = $this->argument('slug');
        $mode = (string) $this->option('mode');

        if (! in_array($mode, ['auto', 'dark', 'light'], true)) {
            $this->error('Mode must be one of: auto, dark, light.');

            return self::FAILURE;
        }

        try {
            $result = $converter->convert(
                source: $source,
                slug: is_string($slug) ? $slug : null,
                name: is_string($this->option('name')) ? $this->option('name') : null,
                mode: $mode,
                force: (bool) $this->option('force'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ((bool) $this->option('activate')) {
            $themes->activate($result->slug);
        }

        $this->info("Theme [{$result->slug}] generated successfully.");
        $this->line("Path: {$result->path}");
        $this->line('Pages: '.implode(', ', $result->pages));

        if ($result->warnings !== []) {
            $this->warn('Warnings:');
            foreach ($result->warnings as $warning) {
                $this->line("- {$warning}");
            }
        }

        return self::SUCCESS;
    }
}

