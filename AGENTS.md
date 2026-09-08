# RichTheme Agent Guide

Use these rules when modifying `richnessagency/rich-theme`.

## Architecture

- Keep the package namespace as `Richness\RichTheme`.
- Keep app-specific integration thin. The package may read `App\Models\Setting`, but storefront templates should consume the public API or CSS variables.
- Do not add theme logic directly to controllers or core views when a `ThemeManager` method or token utility can cover it.
- Theme view overrides must work by prepending `resources/themes/{active}/views` to Laravel's view finder.

## Token Rules

- Emit CSS variables with the `--rc-` prefix only.
- Prefer semantic tokens (`surface.card`, `text.muted`, `status.success`) over raw colors in Blade and CSS.
- Keep default tokens visually compatible with the existing RichCommerce dark design.
- Add new dashboard-editable tokens to `ThemeManager::CUSTOMIZATION_MAP` and validate them before saving.

## Security

- Never accept arbitrary CSS from admin/customer form inputs.
- Keep customizer input fields whitelisted.
- `theme.css` is treated as trusted developer theme code; do not expose editing it to normal customers.
- Do not introduce dynamic PHP execution, uploaded Blade execution, or writable theme PHP files.

## Verification

Run:

```bash
php artisan test packages/rich-theme/tests
```

For layout work, also open `/admin/themes` and `/admin/themes/customize` and inspect storefront pages with both `default` and `light-clean`.

## HTML Converter

Use `php artisan theme:convert {source} {slug}` when the user supplies an organized static HTML template. Keep CSS in `css/`, JavaScript in `js/`, and media in `images/`, `img/`, `fonts/`, or `assets/`. The converter maps common pages, extracts header/footer partials, copies assets, and generates `theme.json`.

For AI-assisted template preparation, read `docs/html-converter.md`, `docs/ai-context.md`, and any project-level `rich-theme-html-converter` skill if the consuming app provides one.

## Future Features

Generated themes must survive new commerce models. Prefer extension markers over hardcoded assumptions:

- `data-rc-hook="storefront.product.digital-delivery"` for filter output slots.
- `data-rc-action="storefront.product.after-gallery"` for event/action slots.
- `data-rc-feature="courses"` around optional feature UI.
- `data-rc-partial="courses.featured"` for optional theme partials.

Keep pages generic enough for physical products, digital products, courses, services, bundles, bookings, and subscriptions.
