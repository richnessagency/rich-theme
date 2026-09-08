# RichTheme HTML Converter

The HTML converter turns an organized static template folder into a RichTheme-compatible theme.

## Command

```bash
php artisan theme:convert {source} {slug?} --name="Theme Name" --mode=auto --force
```

Options:

| Option | Purpose |
|--------|---------|
| `source` | HTML file or directory |
| `slug` | Theme folder name under `resources/themes` |
| `--name` | Human-readable theme name in `theme.json` |
| `--mode=auto` | `auto`, `dark`, or `light` |
| `--force` | Overwrite existing generated theme |
| `--activate` | Activate after conversion |

## Recommended Template Folder

```text
client-template/
├── index.html
├── products.html
├── product.html
├── blog.html
├── article.html
├── css/
│   └── site.css
├── js/
│   └── site.js
└── images/
    └── hero.webp
```

The source can be page-by-page. Header and footer may be repeated in every HTML file; the converter extracts the first header/footer and saves them as shared partials.

## Generated Theme

```text
resources/themes/client-modern/
├── theme.json
├── README.md
├── css/theme.css
├── assets/js/
├── assets/images/
└── views/
    ├── layouts/storefront.blade.php
    ├── partials/theme-header.blade.php
    ├── partials/theme-footer.blade.php
    ├── home.blade.php
    ├── products/index.blade.php
    ├── products/show.blade.php
    ├── blog/index.blade.php
    └── blog/show.blade.php
```

## What The Converter Does

- Generates a `theme.json` matching the RichTheme schema.
- Creates a theme-owned `layouts/storefront.blade.php`.
- Converts each HTML page into a Blade view that extends the theme layout.
- Extracts first `<header>` and `<footer>` into reusable partials.
- Combines `css/*.css` into `css/theme.css`.
- Copies `js/`, `images/`, `img/`, `fonts/`, and `assets/`.
- Rewrites image/font/script references to the safe `rich-theme.asset` route.
- Replaces common hardcoded RichCommerce/Tailwind colors with `var(--rc-*)` tokens and `rc-*` utilities.
- Converts future feature markers such as `data-rc-hook`, `data-rc-action`, `data-rc-feature`, and `data-rc-partial`.

## Future Features

RichCommerce may later support new commerce models such as digital products, courses, subscriptions, bookings, memberships, or downloads. Themes should not need a rewrite every time a new feature ships. Use extension markers in the source HTML so the generated Blade has stable places for future core code or add-ons to render into.

### Markers

| Source HTML | Generated Blade |
|-------------|-----------------|
| `<div data-rc-hook="storefront.home.digital-products"></div>` | `{!! apply_filters('storefront.home.digital_products', '') !!}` |
| `<div data-rc-action="storefront.product.after-gallery"></div>` | `{!! do_action('storefront.product.after_gallery') !!}` |
| `<section data-rc-feature="courses">...</section>` | Wrapped in `StoreConfiguration::feature('courses')` |
| `<div data-rc-partial="courses.featured"></div>` | `@includeIf('partials.courses.featured')` |

Marker names must be semantic and lowercase. Use dots for hierarchy and underscores inside words:

```text
storefront.home.after_hero
storefront.home.digital_products
storefront.products.before_grid
storefront.products.after_grid
storefront.product.after_gallery
storefront.product.digital_delivery
storefront.product.course_outline
storefront.blog.after_article
storefront.account.enrollments
```

### Generated Layout Slots

Every converted theme layout includes these stable filter slots:

```text
storefront.layout.head
storefront.layout.body_start
storefront.layout.before_header
storefront.layout.after_header
storefront.layout.before_content
storefront.layout.after_content
storefront.layout.before_footer
storefront.layout.after_footer
storefront.layout.body_end
```

Use these for scripts, banners, analytics, announcement bars, feature blocks, and add-ons that should not require editing the theme.

### Rules For New Business Models

- Product listing pages should be visually generic enough for physical products, digital products, courses, services, and bundles.
- Product detail pages should reserve extension points near gallery, price, purchase CTA, description, and post-purchase information.
- Course templates should use `data-rc-feature="courses"` around course-only sections and hook names like `storefront.product.course_outline`.
- Digital product templates should use `data-rc-feature="digital_products"` around download/license/delivery sections and hook names like `storefront.product.digital_delivery`.
- Avoid naming CSS classes after one business model unless the section is feature-gated.
- Keep shared assets under `css/`, `js/`, `images/`, `img/`, `fonts/`, or `assets/`.

## Safety

The converter does not execute source HTML. Generated Blade should still be reviewed before production, especially if the source template came from an external vendor.

Theme assets are served through:

```text
/rich-theme-assets/{theme}/{path}
```

The route allows only static file extensions and rejects path traversal.
