<?php

declare(strict_types=1);

namespace Richness\RichTheme\Conversion;

final readonly class ThemeConversionResult
{
    /**
     * @param  list<string>  $pages
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $slug,
        public string $path,
        public array $pages,
        public array $warnings = [],
    ) {}
}

