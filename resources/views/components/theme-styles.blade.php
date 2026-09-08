@php
    $ctx = app(\Richness\RichTheme\ThemeManager::class)->resolve();
@endphp
@if($ctx->fontUrl())
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="{{ $ctx->fontUrl() }}" rel="stylesheet">
@endif
<style id="rc-theme-tokens">
{!! $ctx->toCssVariables() !!}
</style>
@if($ctx->cssPath() && file_exists($ctx->cssPath()))
<style id="rc-theme-css">
{!! file_get_contents($ctx->cssPath()) !!}
</style>
@endif
