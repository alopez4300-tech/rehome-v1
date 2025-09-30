{{-- Profile badge showing current operational profile --}}
@php
    $profile = env('APP_PROFILE', 'light');
    $isLight = $profile === 'light';
@endphp

<div class="flex items-center space-x-2">
    <span class="text-xs font-medium px-2 py-1 rounded-md {{ $isLight ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700' }}">
        {{ $isLight ? '💡 Light' : '🚀 Scale' }}
    </span>
    @if (!$isLight)
        <span class="text-xs text-gray-500">
            ({{ count(array_filter(config('feature', []))) }} features)
        </span>
    @endif
</div>
