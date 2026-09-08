@extends('layouts.admin')

@section('title', 'تخصيص الثيم والمظهر')

@section('content')
@php
    $sections = [
        'brand' => ['title' => 'ألوان العلامة التجارية', 'icon' => 'fa-swatchbook'],
        'surfaces' => ['title' => 'الخلفيات والحدود', 'icon' => 'fa-layer-group'],
        'text' => ['title' => 'ألوان النصوص', 'icon' => 'fa-font'],
        'typography' => ['title' => 'الخطوط', 'icon' => 'fa-text-height'],
        'radius' => ['title' => 'استدارة العناصر', 'icon' => 'fa-vector-square'],
        'layout' => ['title' => 'الهيدر والفوتر', 'icon' => 'fa-window-maximize'],
    ];

    $sectionTokens = collect($customizableTokens)->groupBy('section');
@endphp

<div class="max-w-6xl mx-auto space-y-6">
    <div class="dash-card rounded-2xl p-5 sm:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.themes.index') }}" class="w-8 h-8 rounded-xl bg-[var(--dash-pill-bg)] text-slate-400 hover:text-white flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
                <div class="w-9 h-9 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center font-black">
                    <i class="fa-solid fa-sliders text-base"></i>
                </div>
                <h1 class="text-xl font-black dash-title tracking-tight">
                    تخصيص الثيم الحالي ({{ $context->name }})
                </h1>
            </div>
            <p class="text-xs dash-muted mt-1.5 font-medium">عدّل هوية المتجر المرئية من الداشبورد، والتغييرات تحفظ كطبقة تخصيص فوق ملفات الثيم الأصلية.</p>
        </div>
        <form method="post" action="{{ route('admin.themes.reset') }}" onsubmit="return confirm('هل أنت متأكد من رغبتك في إعادة تعيين التخصيصات والعودة لإعدادات الثيم الأصلية؟');">
            @csrf
            <button type="submit" class="px-3.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-bold transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-rotate-left"></i>
                <span>استعادة القيم الافتراضية</span>
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs sm:text-sm font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs sm:text-sm font-bold space-y-1">
            @foreach($errors->all() as $error)
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <form method="post" action="{{ route('admin.themes.save-customization') }}" class="dash-card rounded-2xl p-6 space-y-7">
            @csrf

            @foreach($sections as $sectionKey => $section)
                @continue(!$sectionTokens->has($sectionKey))

                <section class="space-y-4">
                    <h2 class="text-sm font-black dash-title flex items-center gap-2 border-b border-[var(--dash-border)] pb-3">
                        <i class="fa-solid {{ $section['icon'] }} text-orange-500"></i>
                        <span>{{ $section['title'] }}</span>
                    </h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($sectionTokens[$sectionKey] as $key => $token)
                            @php
                                $inputValue = old($key, $token['value']);
                                $inputId = 'theme_'.$key;
                            @endphp

                            <div class="dash-subcard rounded-2xl p-4 space-y-2">
                                <label for="{{ $inputId }}" class="text-xs font-bold dash-title block">{{ $token['label'] }}</label>

                                @if($token['type'] === 'color')
                                    <div class="flex items-center gap-3">
                                        <input type="color" value="{{ preg_match('/^#[0-9a-fA-F]{6}$/D', (string) $inputValue) ? $inputValue : '#000000' }}"
                                            data-color-picker="{{ $inputId }}"
                                            class="w-12 h-11 rounded-xl bg-[var(--dash-input-bg)] border border-[var(--dash-border)] cursor-pointer p-1">
                                        <input id="{{ $inputId }}" name="{{ $key }}" type="text" value="{{ $inputValue }}" maxlength="7" dir="ltr"
                                            data-token-input="{{ $key }}"
                                            class="font-mono text-xs px-3 py-2.5 rounded-xl bg-[var(--dash-input-bg)] border border-[var(--dash-border)] dash-title">
                                    </div>
                                @else
                                    <input id="{{ $inputId }}" name="{{ $key }}" type="text" value="{{ $inputValue }}" dir="ltr"
                                        data-token-input="{{ $key }}"
                                        class="font-mono text-xs px-3 py-2.5 rounded-xl bg-[var(--dash-input-bg)] border border-[var(--dash-border)] dash-title">
                                @endif

                                <p class="text-[11px] dash-muted font-mono">theme_{{ $key }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-[var(--dash-border)]">
                <a href="{{ route('admin.themes.index') }}" class="px-5 py-2.5 rounded-xl bg-[var(--dash-btn-neutral-bg)] text-[var(--dash-btn-neutral-text)] font-bold text-xs hover:bg-slate-800 transition-colors">
                    إلغاء
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-extrabold text-xs shadow-lg shadow-orange-500/25 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>حفظ التخصيصات</span>
                </button>
            </div>
        </form>

        <aside class="space-y-4">
            <div class="dash-card rounded-2xl p-5 space-y-4 sticky top-24">
                <div>
                    <h2 class="text-sm font-black dash-title">معاينة سريعة</h2>
                    <p class="text-[11px] dash-muted mt-1">المعاينة تساعدك تختبر الهوية قبل الحفظ.</p>
                </div>

                <div id="theme_preview_card" class="rounded-2xl p-4 border space-y-4" style="background: var(--rc-surface-card); border-color: var(--rc-border-default); color: var(--rc-text-body);">
                    <div class="h-16 rounded-xl flex items-center justify-center text-white font-black text-xs shadow-md" style="background: var(--rc-gradient-brand);">
                        تدرج العلامة التجارية
                    </div>
                    <div>
                        <h3 class="font-black text-sm" style="color: var(--rc-text-heading);">عنوان بطاقة المنتج</h3>
                        <p class="text-xs mt-1" style="color: var(--rc-text-muted);">وصف مختصر يوضح شكل النصوص الثانوية داخل الثيم.</p>
                    </div>
                    <button type="button" class="w-full h-11 rounded-xl text-white font-bold text-xs shadow-lg" style="background: var(--rc-btn-primary-bg); box-shadow: var(--rc-btn-primary-shadow);">
                        زر الشراء التجريبي
                    </button>
                </div>

                <div class="dash-subcard rounded-2xl p-4 overflow-x-auto max-h-56">
                    <table class="w-full text-right text-xs">
                        <tbody class="divide-y divide-[var(--dash-border)] font-mono">
                            @foreach(array_slice($tokens, 0, 12) as $varName => $varVal)
                                <tr>
                                    <td class="py-1.5 text-orange-400 text-[11px]">{{ $varName }}</td>
                                    <td class="py-1.5 dash-title text-[11px]">{{ $varVal }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
document.querySelectorAll('[data-color-picker]').forEach((picker) => {
    const target = document.getElementById(picker.dataset.colorPicker);
    if (!target) return;

    picker.addEventListener('input', () => {
        target.value = picker.value;
        target.dispatchEvent(new Event('input'));
    });

    target.addEventListener('input', () => {
        if (/^#[0-9a-fA-F]{6}$/.test(target.value)) {
            picker.value = target.value;
        }
    });
});
</script>
@endsection
