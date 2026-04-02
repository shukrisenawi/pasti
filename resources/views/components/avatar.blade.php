@props([
    'user' => null,
    'guru' => null,
    'size' => 'h-9 w-9',
    'rounded' => 'rounded-full',
    'border' => 'border border-base-300',
    'class' => ''
])

@php
    $targetGuru = $guru ?? ($user?->guru ?? null);
    $avatarUrl = $targetGuru?->avatar_url ?? $user?->avatar_url ?? '/images/default-avatar.svg';
    $name = $targetGuru?->display_name ?? $user?->display_name ?? 'Avatar';
    $hasAward = ($targetGuru?->kursus_guru ?? null) === 'terima_anugerah' || ($targetGuru?->terima_anugerah ?? false);
@endphp

<div class="relative inline-block shrink-0 {{ $class }}" style="position:relative; display:inline-block; flex-shrink:0;">
    <img src="{{ $avatarUrl }}" alt="{{ $name }}" class="{{ $size }} {{ $rounded }} {{ $border }} object-cover">
    @if($hasAward)
        <span
            title="{{ __('messages.terima_anugerah') }}"
            style="position:absolute; bottom:-2px; right:-2px; display:flex; align-items:center; justify-content:center; width:16px; height:16px; border-radius:9999px; background-color:#3b82f6; box-shadow:0 1px 3px rgba(0,0,0,0.3); outline:2px solid white; z-index:10;"
        >
            <svg width="10" height="10" fill="white" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
        </span>
    @endif
</div>
