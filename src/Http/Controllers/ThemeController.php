<?php

declare(strict_types=1);

namespace Richness\RichTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Richness\RichTheme\ThemeManager;

final class ThemeController extends Controller
{
    public function index(ThemeManager $manager): View
    {
        return view('rich-theme::admin.themes.index', [
            'themes' => $manager->availableThemes(),
            'activeTheme' => $manager->activeThemeName(),
        ]);
    }

    public function activate(string $name, ThemeManager $manager): RedirectResponse
    {
        $validation = $manager->validate($name);
        if (! $validation['valid']) {
            return redirect()->route('admin.themes.index')
                ->with('error', 'الثيم غير صالح: '.implode(', ', $validation['errors']));
        }

        $manager->activate($name);

        return redirect()->route('admin.themes.index')
            ->with('success', 'تم تفعيل الثيم بنجاح! قد تحتاج لمسح الكاش: php artisan view:clear');
    }

    public function customize(ThemeManager $manager): View
    {
        $context = $manager->resolve();

        return view('rich-theme::admin.themes.customize', [
            'context' => $context,
            'customizableTokens' => $manager->customizableTokens($context),
            'tokens' => $context->toCssVariablesMap(),
        ]);
    }

    public function saveCustomization(Request $request, ThemeManager $manager): RedirectResponse
    {
        $colorRule = 'regex:/^(?:#[0-9a-fA-F]{6}|rgba?\(\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\))$/D';

        $validated = $request->validate([
            'primary' => ['nullable', $colorRule],
            'primary_hover' => ['nullable', $colorRule],
            'accent' => ['nullable', $colorRule],
            'accent_hover' => ['nullable', $colorRule],
            'surface_page' => ['nullable', $colorRule],
            'surface_card' => ['nullable', $colorRule],
            'surface_input' => ['nullable', $colorRule],
            'border_default' => ['nullable', $colorRule],
            'border_input' => ['nullable', $colorRule],
            'text_heading' => ['nullable', $colorRule],
            'text_body' => ['nullable', $colorRule],
            'text_muted' => ['nullable', $colorRule],
            'font_body' => ['nullable', 'string', 'max:220', 'not_regex:/[;{}<>]/'],
            'font_display' => ['nullable', 'string', 'max:220', 'not_regex:/[;{}<>]/'],
            'radius_sm' => ['nullable', 'regex:/^(?:0|[0-9]{1,3}(?:\.[0-9]{1,2})?(?:px|rem|em|%|vh|vw))$/D'],
            'radius_md' => ['nullable', 'regex:/^(?:0|[0-9]{1,3}(?:\.[0-9]{1,2})?(?:px|rem|em|%|vh|vw))$/D'],
            'radius_lg' => ['nullable', 'regex:/^(?:0|[0-9]{1,3}(?:\.[0-9]{1,2})?(?:px|rem|em|%|vh|vw))$/D'],
            'radius_xl' => ['nullable', 'regex:/^(?:0|[0-9]{1,3}(?:\.[0-9]{1,2})?(?:px|rem|em|%|vh|vw))$/D'],
            'header_height' => ['nullable', 'regex:/^(?:0|[0-9]{1,3}(?:\.[0-9]{1,2})?(?:px|rem|em|%|vh|vw))$/D'],
            'header_bg' => ['nullable', $colorRule],
            'footer_bg' => ['nullable', $colorRule],
            'footer_border' => ['nullable', $colorRule],
        ]);

        $manager->saveCustomization(array_filter($validated));

        return redirect()->route('admin.themes.customize')
            ->with('success', 'تم حفظ التخصيصات بنجاح');
    }

    public function resetCustomization(ThemeManager $manager): RedirectResponse
    {
        $manager->resetCustomization();

        return redirect()->route('admin.themes.customize')
            ->with('success', 'تم إعادة تعيين التخصيصات إلى القيم الافتراضية');
    }
}
