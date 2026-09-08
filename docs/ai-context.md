# RichTheme Engine — AI Agent Context

> Compact reference for AI coding assistants working on the RichCommerce theming system.
> Read this file first before modifying any theme-related code.

## System Overview

RichCommerce uses a WordPress-inspired theming engine called **RichTheme**, implemented as a **local Composer package** at `packages/rich-theme/`. Themes are directories under `resources/themes/` that can override Blade views, inject CSS design tokens, and include custom CSS.

## Package Info

| Property | Value |
|----------|-------|
| **Location** | `packages/rich-theme/` |
| **Namespace** | `Richness\RichTheme` |
| **Composer name** | `richnessagency/rich-theme` |
| **Type** | Local path repository |
| **Facade** | `Richness\RichTheme\Facades\Theme` (alias: `Theme`) |

## Active Theme Resolution

```
DB setting 'active_theme' → config('theme.active') → env('THEME_ACTIVE') → 'default'
```

Token override priority (highest first):
1. DB settings (`theme_primary`, `theme_accent`, etc.)
2. Active theme's `theme.json`
3. Default theme's `theme.json`
4. `Richness\RichTheme\Support\DefaultTokens::all()`

## Key Files

### Package (packages/rich-theme/)
| File | Purpose |
|------|---------|
| `src/ThemeContext.php` | Immutable value object — all resolved tokens |
| `src/ThemeManager.php` | Core service — loads themes, resolves tokens, manages activation |
| `src/ThemeServiceProvider.php` | Registers singleton, boots view overrides, publishes config |
| `src/Facades/Theme.php` | `Theme::color('primary')`, `Theme::css()`, etc. |
| `src/Http/Controllers/ThemeController.php` | Admin theme gallery + customizer |
| `src/Support/DefaultTokens.php` | Hardcoded fallback token values |
| `config/theme.php` | Publishable config (active theme, path, cache) |
| `resources/views/components/theme-styles.blade.php` | Blade component injecting CSS vars |
| `resources/views/admin/themes/index.blade.php` | Theme gallery admin page |
| `resources/views/admin/themes/customize.blade.php` | Token customizer admin page |
| `routes/admin.php` | Admin routes for theme management |

### Main App (comerce-base)
| File | Purpose |
|------|---------|
| `resources/themes/{name}/theme.json` | Theme token definitions |
| `resources/themes/{name}/views/` | Blade template overrides |
| `resources/themes/{name}/css/theme.css` | Optional theme CSS |
| `resources/css/app.css` | Tailwind v4 `@theme` rc-* mappings + utility classes |
| `resources/css/static-storefront.css` | Legacy vars aliased to `--rc-*` |

## CSS Variable Namespace

All CSS custom properties use `--rc-` prefix (RichCommerce):

```
--rc-primary              Brand primary color (#f97316)
--rc-primary-hover        Primary hover state
--rc-primary-light        Primary at low opacity
--rc-primary-shadow       Primary for shadows
--rc-accent               Secondary brand color
--rc-accent-hover         Accent hover state
--rc-surface-page         Page background
--rc-surface-card         Card/panel background
--rc-surface-raised       Elevated surface (glass headers)
--rc-surface-input        Form input background
--rc-surface-overlay      Modal backdrop
--rc-surface-subcard      Nested card background
--rc-border-default       Default border color
--rc-border-hover         Border hover state
--rc-border-input         Form input border
--rc-text-heading         Heading text color
--rc-text-body            Body text color
--rc-text-muted           Muted/secondary text
--rc-text-inverse         Inverse text (dark on light)
--rc-status-success       Green
--rc-status-warning       Yellow/amber
--rc-status-danger        Red
--rc-status-info          Blue
--rc-gradient-brand       Brand gradient (primary → accent)
--rc-gradient-brand-text  Text gradient
--rc-font-body            Body font-family
--rc-font-display         Heading font-family
--rc-radius-sm/md/lg/xl/full  Border radius scale
--rc-header-height/bg/blur/border  Header styling
--rc-footer-bg/border     Footer styling
--rc-btn-primary-bg/text/shadow  Primary button
--rc-btn-neutral-bg/text/border  Neutral button
```

## Blade Usage Patterns

```blade
{{-- In <head> - injects tokens + theme CSS --}}
@include('rich-theme::components.theme-styles')

{{-- PHP access via Facade --}}
{{ Theme::color('primary') }}
{{ Theme::isDark() ? 'dark' : 'light' }}

{{-- CSS utility classes --}}
<div class="rc-bg-card rc-text-body rc-border">
<h2 class="rc-text-heading">
<a class="rc-text-primary hover:rc-text-primary">
<div class="rc-gradient">

{{-- Tailwind v4 with rc-* colors --}}
<div class="bg-rc-surface-card text-rc-text-body border-rc-border">

{{-- Inline with vars --}}
<div style="background: var(--rc-gradient-brand);">
```

## View Override System

Theme views path prepended to Laravel's view finder:
```
resources/themes/{active}/views/products/show.blade.php  ← checked first
resources/views/products/show.blade.php                  ← fallback
```
No controller changes needed. Paths must mirror `resources/views/` structure.

## HTML Converter Future Markers

`php artisan theme:convert` understands safe extension markers in source HTML:

| Marker | Purpose |
|--------|---------|
| `data-rc-hook="storefront.home.digital-products"` | Replaced with `apply_filters('storefront.home.digital_products', '')` |
| `data-rc-action="storefront.product.after-gallery"` | Replaced with `do_action('storefront.product.after_gallery')` |
| `data-rc-feature="courses"` | Wraps the element in `StoreConfiguration::feature('courses')` |
| `data-rc-partial="courses.featured"` | Replaced with `@includeIf('partials.courses.featured')` |

Converted layouts also include stable filter slots around `head`, `body_start`, `before_header`, `after_header`, `before_content`, `after_content`, `before_footer`, `after_footer`, and `body_end`.

For future features like digital products, courses, subscriptions, bookings, memberships, and downloads, design templates as generic containers and add these markers near the feature's visual location instead of hardcoding business logic.

## Admin Routes

| Route | Method | Controller |
|-------|--------|------------|
| `GET /admin/themes` | `index` | Theme gallery |
| `POST /admin/themes/{name}/activate` | `activate` | Switch theme |
| `GET /admin/themes/customize` | `customize` | Token editor |
| `POST /admin/themes/customize` | `saveCustomization` | Save overrides |
| `POST /admin/themes/reset` | `resetCustomization` | Reset to defaults |

Nav: System group, icon `fa-palette`, label "الثيمات والمظهر"

## Dashboard Customization Keys

Customer/admin overrides are stored in `settings` using a strict whitelist. Never save arbitrary CSS from the dashboard.

| Setting key | Token path | Accepted values |
|-------------|------------|-----------------|
| `theme_primary` | `colors.primary` | `#RRGGBB`, `rgb(...)`, `rgba(...)` |
| `theme_primary_hover` | `colors.primary-hover` | color |
| `theme_accent` | `colors.accent` | color |
| `theme_accent_hover` | `colors.accent-hover` | color |
| `theme_surface_page` | `colors.surface.page` | color |
| `theme_surface_card` | `colors.surface.card` | color |
| `theme_surface_input` | `colors.surface.input` | color |
| `theme_border_default` | `colors.border.default` | color |
| `theme_border_input` | `colors.border.input` | color |
| `theme_text_heading` | `colors.text.heading` | color |
| `theme_text_body` | `colors.text.body` | color |
| `theme_text_muted` | `colors.text.muted` | color |
| `theme_font_body` | `typography.font-body` | font-family string without `;{}<>` |
| `theme_font_display` | `typography.font-display` | font-family string without `;{}<>` |
| `theme_radius_sm/md/lg/xl` | `radius.*` | bounded CSS length |
| `theme_header_height` | `header.height` | bounded CSS length |
| `theme_header_bg` | `header.bg` | color |
| `theme_footer_bg` | `footer.bg` | color |
| `theme_footer_border` | `footer.border` | color |

## Migration Rules (Hardcoded → Tokens)

| Before (hardcoded) | After (themed) |
|---------------------|----------------|
| `bg-slate-950` | `rc-bg-page` |
| `bg-slate-900` | `rc-bg-card` |
| `text-orange-400` | `rc-text-primary` |
| `hover:text-orange-400` | `hover:rc-text-primary` |
| `bg-orange-500` | `rc-bg-primary` |
| `border-slate-800` | `rc-border` |
| `text-slate-100` | `rc-text-body` |
| `text-slate-400` | `rc-text-muted` |
| `text-white` (on dark bg) | `rc-text-heading` |
| `shadow-orange-500/30` | `shadow-[var(--rc-primary-shadow)]` |
| `#0f172a` (inline) | `var(--rc-surface-page)` |
| `rgba(15, 23, 42, 0.85)` | `var(--rc-surface-raised)` |

## Constraints

- PHP 8.3+, Laravel 13, Tailwind CSS v4
- Arabic RTL first
- Shared hosting compatible
- No Vite rebuild for token/view changes
- Package auto-discovered via `extra.laravel.providers`
- Admin dark/light toggle works independently of storefront theme
