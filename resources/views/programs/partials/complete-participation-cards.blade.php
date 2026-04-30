<div class="grid items-start gap-3 md:grid-cols-2">
    @forelse($participations as $participation)
        <article data-testid="program-complete-card" class="self-start rounded-2xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    <x-avatar :guru="$participation->guru" size="h-10 w-10" rounded="rounded-xl" border="border border-slate-200" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="truncate text-sm font-black text-slate-900">{{ $participation->guru->display_name }}</h3>
                        <span class="inline-flex shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700">
                            {{ $participation->status?->name ?? '-' }}
                        </span>
                    </div>

                    @if($participation->status?->code !== 'HADIR')
                        <p class="mt-1 truncate text-xs text-slate-500">
                            <span class="font-semibold text-slate-600">{{ __('messages.absence_reason') }}:</span>
                            {{ $participation->absence_reason ?? '-' }}
                        </p>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500 md:col-span-2">
            {{ $emptyMessage ?? '-' }}
        </div>
    @endforelse
</div>
