# RichTheme Engine

WordPress-style theming engine for RichCommerce. It lets each customer switch a storefront theme, override Blade views, and customize design tokens from the admin dashboard without changing `.env` or rebuilding Vite assets.

## Core Idea

RichTheme resolves the active theme in this order:

1. DB setting: `active_theme`
2. `config('theme.active')`
3. `THEME_ACTIVE`
4. `default`

Then it merges tokens in this order:

1. `DefaultTokens::all()`
2. `resources/themes/{active}/theme.json`
3. DB overrides such as `theme_primary`, `theme_surface_card`, `theme_radius_lg`

## Theme Structure

```text
resources/themes/client-name/
├── theme.json
├── screenshot.png
├── views/
│   └── products/show.blade.php
├── css/
│   └── theme.css
└── assets/
```

Any file under `views/` overrides the matching app view path. For example:

```text
resources/themes/luxury/views/products/show.blade.php
```

overrides:

```text
resources/views/products/show.blade.php
```

## PHP API

```php
use Richness\RichTheme\Facades\Theme;

Theme::color('primary');
Theme::surface('card');
Theme::text('heading');
Theme::gradient('brand');
Theme::font('body');
Theme::isDark();
Theme::availableThemes();
Theme::activate('light-clean');
```

## Blade Integration

Put the theme style component in the `<head>` of any layout:

```blade
@include('rich-theme::components.theme-styles')
```

Use token utilities in views:

```blade
<section class="rc-bg-page rc-text-body">
    <article class="rc-bg-card rc-border">
        <h1 class="rc-text-heading">عنوان المنتج</h1>
        <a class="rc-text-primary hover:rc-text-primary">شراء الآن</a>
    </article>
</section>
```

## Admin

Routes:

```text
GET  /admin/themes
GET  /admin/themes/customize
POST /admin/themes/customize
POST /admin/themes/reset
POST /admin/themes/{name}/activate
```

The customizer stores only whitelisted token overrides. It rejects unsafe CSS values and unknown keys.

## Security Rules

- `theme.json` and `css/theme.css` are developer-controlled files, not customer text fields.
- Customer/admin DB overrides are whitelisted and validated before storage.
- Color overrides accept strict `#RRGGBB`, `rgb(...)`, or `rgba(...)` only.
- Length overrides accept bounded `px`, `rem`, `em`, `%`, `vh`, or `vw` values only.
- Font overrides reject `;`, `{`, `}`, `<`, and `>` to prevent style/script injection.
- The package does not write PHP files or evaluate theme code dynamically.

## Testing

```bash
php artisan test vendor/richnessagency/rich-theme/tests
```

## HTML Converter

Convert an organized HTML/CSS/JS template folder into a theme:

```bash
php artisan theme:convert /path/to/template client-modern --name="Client Modern" --mode=auto --force
```

Expected input:

```text
template/
├── index.html
├── products.html
├── product.html
├── blog.html
├── article.html
├── css/
├── js/
└── images/
```

See [docs/html-converter.md](docs/html-converter.md) and [docs/ai-context.md](docs/ai-context.md) for converter rules and AI-assisted theme preparation guidance.

### Future Feature Markers

Source HTML can include extension markers:

```html
<div data-rc-hook="storefront.home.digital-products"></div>
<section data-rc-feature="courses">...</section>
<div data-rc-partial="courses.featured"></div>
```

The converter turns these into Blade hook slots, feature guards, and optional partials so generated themes do not break when RichCommerce adds digital products, courses, subscriptions, bookings, or similar modules.
