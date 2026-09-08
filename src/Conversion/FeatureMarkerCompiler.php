<?php

declare(strict_types=1);

namespace Richness\RichTheme\Conversion;

final class FeatureMarkerCompiler
{
    public function compile(string $html): string
    {
        $html = $this->replaceWholeElementMarker($html, 'data-rc-hook', static fn (string $name): string => "{!! apply_filters('{$name}', '') !!}");
        $html = $this->replaceWholeElementMarker($html, 'data-rc-action', static fn (string $name): string => "{!! do_action('{$name}') !!}");
        $html = $this->replaceWholeElementMarker($html, 'data-rc-partial', static fn (string $name): string => "@includeIf('partials.{$name}')");

        return $this->wrapFeatureElements($html);
    }

    /**
     * @param  callable(string): string  $replacement
     */
    private function replaceWholeElementMarker(string $html, string $attribute, callable $replacement): string
    {
        $pattern = '/<([a-z][a-z0-9:-]*)\b([^>]*)\s'.$attribute.'=["\']([a-zA-Z0-9_.:-]+)["\']([^>]*)>(.*?)<\/\1>/is';

        return preg_replace_callback($pattern, function (array $matches) use ($replacement): string {
            $name = $this->normalizeMarkerName((string) $matches[3]);

            return $name === null ? $matches[0] : $replacement($name);
        }, $html) ?? $html;
    }

    private function wrapFeatureElements(string $html): string
    {
        $pattern = '/<([a-z][a-z0-9:-]*)\b([^>]*)\sdata-rc-feature=["\']([a-zA-Z0-9_.:-]+)["\']([^>]*)>(.*?)<\/\1>/is';

        return preg_replace_callback($pattern, function (array $matches): string {
            $feature = $this->normalizeMarkerName((string) $matches[3]);
            if ($feature === null) {
                return $matches[0];
            }

            $element = '<'.$matches[1].$matches[2].$matches[4].'>'.$matches[5].'</'.$matches[1].'>';

            return "@if(\\App\\Domain\\Store\\StoreConfiguration::feature('{$feature}'))\n".$element."\n@endif";
        }, $html) ?? $html;
    }

    private function normalizeMarkerName(string $name): ?string
    {
        $name = strtolower(str_replace([':', '-'], ['.', '_'], trim($name)));

        return preg_match('/^[a-z0-9_]+(?:\.[a-z0-9_]+)*$/D', $name) === 1 ? $name : null;
    }
}

