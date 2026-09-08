<?php

declare(strict_types=1);

namespace Richness\RichTheme\Facades;

use Illuminate\Support\Facades\Facade;
use Richness\RichTheme\ThemeManager;

/**
 * @method static \Richness\RichTheme\ThemeContext context()
 * @method static string color(string $key)
 * @method static string surface(string $key)
 * @method static string text(string $key)
 * @method static string border(string $key)
 * @method static string gradient(string $key)
 * @method static string font(string $key)
 * @method static string css()
 * @method static bool isDark()
 * @method static bool isLight()
 * @method static array availableThemes()
 * @method static string activeThemeName()
 * @method static void activate(string $name)
 * @method static void saveCustomization(array $tokens)
 * @method static void resetCustomization()
 * @method static \Richness\RichTheme\ThemeContext load(string $name)
 * @method static \Richness\RichTheme\ThemeContext resolve()
 *
 * @see ThemeManager
 */
class Theme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'theme';
    }
}
