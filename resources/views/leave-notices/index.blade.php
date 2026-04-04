<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.leave_notice') }}</h2>
            @role('guru')
                <a href="{{ route('leave-notices.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
            @endrole
        </div>
    </x-slot>

    {{-- Mobile View --}}
    <div class="grid grid-cols-1 gap-4 md:hidden">
        @forelse($leaveNotices as $notice)
            <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-50 relative overflow-hidden">
                <div class="flex flex-col gap-3">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center gap-3 mb-3">
                            <x-avatar :guru="$notice->guru" size="h-10 w-10" rounded="rounded-2xl" />
                            <div>
                                @if($showAdminColumns)
                                    <h3 class="font-extrabold text-slate-900 leading-tight">{{ $notice->guru?->display_name ?? '-' }}</h3>
                                @else
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Tarikh Cuti</p>
                                @endif
                                <div class="flex items-center gap-2 text-slate-700">
                                    <span class="font-bold">{{ $notice->leave_date?->format('d/m/Y') }}</span>
                                    @if($notice->leave_until && $notice->leave_until->ne($notice->leave_date))
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                        <span class="font-bold">{{ $notice->leave_until?->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        @if($notice->mc_image_url)
                            <a href="{{ $notice->mc_image_url }}" target="_blank" class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.306 5 12 5c4.694 0 8.601 3.049 9.964 6.678a1.012 1.012 0 010 .644C20.601 15.951 16.694 19 12 19c-4.694 0-8.601-3.049-9.964-6.678z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
                        @endif
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Sebab/Catatan</p>
                        <p class="text-sm text-slate-600 leading-relaxed italic">"{{ $notice->reason }}"</p>
                    </div>

                    @php
                        $canDeleteNotice = $canDeleteAll
                            || (!empty($assignedPastiIds) && in_array((int) ($notice->guru?->pasti_id ?? 0), $assignedPastiIds, true));
                    @endphp
                    
                    @if($canDeleteNotice)
                        <div class="flex justify-end pt-2">
                            <form method="POST" action="{{ route('leave-notices.destroy', $notice) }}" class="m-0">
                                @csrf
                                @method('DELETE')
                                <button class="flex items-center gap-2 px-4 py-2 rounded-xl text-rose-600 bg-rose-50 text-xs font-bold transition-colors active:bg-rose-100" onclick="return confirm('Hapus rekod ini?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                    {{ __('messages.delete') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-10 bg-white rounded-2xl border-2 border-dashed border-slate-100 text-slate-400 font-medium font-manrope">
                Tiada rekod permohonan cuti.
            </div>
        @endforelse
    </div>

    {{-- Desktop View --}}
    <div class="table-wrap hidden md:block">
        <table class="table-base">
            <thead>
            <tr>
                @if($showAdminColumns)
                    <th>{{ __('messages.name') }}</th>
                @endif
                <th>{{ __('messages.leave_date') }}</th>
                <th>{{ __('messages.leave_until') }}</th>
                <th>{{ __('messages.reason') }}</th>
                <th class="text-center">{{ __('messages.mc_attachment') }}</th>
                <th class="text-right">{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            @forelse($leaveNotices as $notice)
                <tr class="hover:bg-slate-50 transition-colors">
                    @if($showAdminColumns)
                        <td class="flex items-center gap-3">
                            <x-avatar :guru="$notice->guru" size="h-10 w-10" rounded="rounded-2xl" />
                            <span class="font-bold text-slate-700">{{ $notice->guru?->display_name ?? '-' }}</span>
                        </td>
                    @endif
                    <td class="font-semibold text-slate-700">{{ $notice->leave_date?->format('d/m/Y') }}</td>
                    <td class="font-semibold text-slate-700">{{ ($notice->leave_until ?? $notice->leave_date)?->format('d/m/Y') }}</td>
                    <td class="max-w-sm whitespace-pre-wrap text-slate-500 text-sm leading-relaxed px-4 py-3">{{ $notice->reason }}</td>
                    <td class="text-center">
                        @if($notice->mc_image_url)
                            <a href="{{ $notice->mc_image_url }}" target="_blank" class="inline-flex items-center justify-center p-2 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors" title="{{ __('messages.view') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.306 5 12 5c4.694 0 8.601 3.049 9.964 6.678a1.012 1.012 0 010 .644C20.601 15.951 16.694 19 12 19c-4.694 0-8.601-3.049-9.964-6.678z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
                        @else
                            <span class="text-slate-300">-</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @php
                            $canDeleteNotice = $canDeleteAll
                                || (!empty($assignedPastiIds) && in_array((int) ($notice->guru?->pasti_id ?? 0), $assignedPastiIds, true));
                        @endphp
                        @if($canDeleteNotice)
                            <form method="POST" action="{{ route('leave-notices.destroy', $notice) }}" class="inline m-0">
                                @csrf
                                @method('DELETE')
                                <button class="p-2 text-rose-400 hover:text-rose-600 transition-colors" onclick="return confirm('Hapus rekod ini?')" title="{{ __('messages.delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        @else
                            <span class="text-slate-300">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $showAdminColumns ? 6 : 5 }}" class="text-center py-8 text-slate-400">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $leaveNotices->links() }}</div>
</x-app-layout>

