@extends('layouts.admin')

@section('title', 'الثيمات والمظهر')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="dash-card rounded-2xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center font-black">
                    <i class="fa-solid fa-palette text-base"></i>
                </div>
                <h1 class="text-xl font-black dash-title tracking-tight">
                    معرض الثيمات والمظهر
                </h1>
            </div>
            <p class="text-xs dash-muted mt-1.5 font-medium">اختر الثيم النشط لمتجرك أو خصص ألوان وهوية الثيم الحالي بكل سهولة بدون الحاجة لإعادة بناء الأصول</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.themes.customize') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs transition-all shadow-lg shadow-orange-500/20">
                <i class="fa-solid fa-sliders"></i>
                <span>تخصيص الألوان والرموز</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs sm:text-sm font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs sm:text-sm font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-rose-400"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Themes Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($themes as $slug => $theme)
            @php
                $isActive = $activeTheme === $slug;
            @endphp
            <div class="dash-card rounded-2xl overflow-hidden flex flex-col justify-between transition-all duration-200 hover:border-orange-500/30 {{ $isActive ? 'ring-2 ring-emerald-500 border-emerald-500/40 shadow-lg shadow-emerald-500/10' : '' }}">
                <!-- Preview / Screenshot Header -->
                <div class="h-48 relative overflow-hidden bg-slate-900 flex items-center justify-center border-b border-[var(--dash-border)]">
                    @if(!empty($theme['screenshot']))
                        <img src="{{ $theme['screenshot'] }}" alt="{{ $theme['name'] }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center p-6 text-center {{ $theme['mode'] === 'light' ? 'bg-gradient-to-br from-slate-100 to-slate-200 text-slate-800' : 'bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 text-slate-200' }}">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg {{ $theme['mode'] === 'light' ? 'bg-white text-blue-600 shadow-blue-500/10' : 'bg-slate-800 text-orange-400 shadow-orange-500/10 border border-slate-700' }}">
                                <i class="fa-solid {{ $theme['mode'] === 'light' ? 'fa-sun text-2xl' : 'fa-moon text-2xl' }}"></i>
                            </div>
                            <span class="mt-3 font-extrabold text-sm tracking-wide">{{ $theme['name'] }}</span>
                            <span class="text-[11px] opacity-70">{{ $theme['mode'] === 'light' ? 'ثيم فاتح ونقي' : 'ثيم داكن احترافي' }}</span>
                        </div>
                    @endif

                    <!-- Mode and Status Badges -->
                    <div class="absolute top-3 right-3 flex items-center gap-1.5">
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold backdrop-blur-md shadow-sm {{ $theme['mode'] === 'light' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-slate-800/80 text-slate-300 border border-slate-700' }}">
                            <i class="fa-solid {{ $theme['mode'] === 'light' ? 'fa-sun' : 'fa-moon' }} ml-1"></i>
                            {{ $theme['mode'] === 'light' ? 'فاتح' : 'داكن' }}
                        </span>
                    </div>

                    @if($isActive)
                        <div class="absolute top-3 left-3">
                            <span class="px-3 py-1 rounded-full text-[11px] font-black bg-emerald-500 text-white shadow-md shadow-emerald-500/30 flex items-center gap-1">
                                <i class="fa-solid fa-check text-[10px]"></i>
                                <span>مفعّل</span>
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Theme Info Body -->
                <div class="p-5 flex-1 flex flex-col justify-between gap-4">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-base font-black dash-title">{{ $theme['name'] }}</h3>
                            <code class="text-[10px] font-mono px-2 py-0.5 rounded bg-[var(--dash-pill-bg)] dash-muted">{{ $slug }}</code>
                        </div>
                        <p class="text-xs dash-muted mt-2 leading-relaxed">
                            {{ $theme['description'] ?: 'ثيم مصمم لمتجر RichCommerce.' }}
                        </p>
                    </div>

                    <!-- Meta Tags -->
                    <div class="flex items-center gap-2 pt-2 border-t border-[var(--dash-border)]">
                        @if($theme['has_views'])
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                <i class="fa-solid fa-code ml-1"></i> قوالب مخصصة
                            </span>
                        @else
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                <i class="fa-solid fa-fill-drip ml-1"></i> تصميم الرموز والألوان
                            </span>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="pt-3">
                        @if($isActive)
                            <button type="button" disabled class="w-full py-2.5 px-4 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 font-bold text-xs flex items-center justify-center gap-2 cursor-default">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>الثيم المفعّل حالياً للمتجر</span>
                            </button>
                        @else
                            <form method="post" action="{{ route('admin.themes.activate', $slug) }}">
                                @csrf
                                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-[var(--dash-btn-neutral-bg)] hover:bg-orange-500 hover:text-white text-[var(--dash-btn-neutral-text)] border border-[var(--dash-border)] font-bold text-xs transition-all flex items-center justify-center gap-2 shadow-sm">
                                    <i class="fa-solid fa-power-off"></i>
                                    <span>تفعيل هذا الثيم</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
