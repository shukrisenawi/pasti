<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.guru') }}</h2>
            <a href="{{ route('users.gurus.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
        </div>
    </x-slot>

    <div class="mb-6">
        <div class="flex p-1 bg-slate-100 rounded-xl w-fit">
            <a href="{{ route('users.gurus.index', ['tab' => 'guru']) }}" 
               class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $activeTab === 'guru' ? 'bg-white shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700' }}">
                {{ __('messages.main_teacher') }} 
                <span class="ml-1 opacity-60">({{ $guruCount }})</span>
            </a>
            <a href="{{ route('users.gurus.index', ['tab' => 'assistant']) }}" 
               class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $activeTab === 'assistant' ? 'bg-white shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700' }}">
                {{ __('messages.assistant_teacher') }}
                <span class="ml-1 opacity-60">({{ $assistantCount }})</span>
            </a>
        </div>
    </div>

    {{-- Mobile Card View --}}
    <div class="grid grid-cols-1 gap-4 md:hidden">
        @forelse($gurus as $guru)
            <div class="bg-white rounded-2xl shadow-card border border-slate-50 overflow-hidden group">
                <div class="p-4">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <x-avatar :guru="$guru" size="h-12 w-12" />
                            <div>
                                <h3 class="font-bold text-slate-900 leading-tight">{{ $guru->display_name }}</h3>
                                <div class="flex items-center gap-1 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $guru->active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                                        {{ $guru->active ? __('messages.active') : __('messages.inactive') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                             <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400 mb-0.5">KPI SCORE</div>
                             <div class="text-lg font-black text-primary">{{ number_format((float) ($guru->kpiSnapshot?->score ?? 0), 2) }}%</div>
                        </div>
                    </div>

                    <div class="space-y-2.5 mb-5 border-t border-slate-50 pt-4">
                        <div class="flex items-center gap-2.5 text-slate-600 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-slate-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                            </svg>
                            <span class="truncate">{{ $guru->pasti?->name ?? '-' }}</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-slate-600 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-slate-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                            </svg>
                            <span>{{ $guru->phone ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-50 pt-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('kpi.guru.show', $guru) }}" class="p-2 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.306 5 12 5c4.694 0 8.601 3.049 9.964 6.678a1.012 1.012 0 010 .644C20.601 15.951 16.694 19 12 19c-4.694 0-8.601-3.049-9.964-6.678z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
                            <a href="{{ route('users.gurus.edit', $guru) }}" class="p-2 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </a>
                        </div>
                        <form method="POST" action="{{ route('users.gurus.destroy', $guru) }}" class="inline m-0">
                            @csrf
                            @method('DELETE')
                            <button class="p-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors" onclick="return confirm('Delete?')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center bg-white p-8 rounded-2xl border-2 border-dashed border-slate-100 text-slate-400">
                {{ __('No records found') }}
            </div>
        @endforelse
    </div>

    {{-- Desktop Table View --}}
    <div class="table-wrap hidden md:block">
        <table class="table-base">
            <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.pasti') }}</th>
                <th>{{ __('messages.phone') }}</th>
                <th>{{ __('messages.status') }}</th>
                <th>{{ __('messages.kpi_score') }}</th>
                <th class="text-right">{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($gurus as $guru)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td>
                        <div class="flex items-center gap-3">
                            <x-avatar :guru="$guru" />
                            <span class="font-medium text-slate-700">{{ $guru->display_name }}</span>
                        </div>
                    </td>
                    <td>{{ $guru->pasti?->name ?? '-' }}</td>
                    <td>{{ $guru->phone ?? '-' }}</td>
                    <td>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $guru->active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                            {{ $guru->active ? __('messages.active') : __('messages.inactive') }}
                        </span>
                    </td>
                    <td>
                        <span class="font-bold text-primary">{{ number_format((float) ($guru->kpiSnapshot?->score ?? 0), 2) }}%</span>
                    </td>
                    <td class="flex items-center justify-end gap-1 px-4 py-3">
                        <a href="{{ route('kpi.guru.show', $guru) }}" class="btn btn-ghost btn-xs btn-circle text-primary hover:bg-primary/10 transition-colors" title="{{ __('messages.view') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.306 5 12 5c4.694 0 8.601 3.049 9.964 6.678a1.012 1.012 0 010 .644C20.601 15.951 16.694 19 12 19c-4.694 0-8.601-3.049-9.964-6.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
                        <a href="{{ route('users.gurus.edit', $guru) }}" class="btn btn-ghost btn-xs btn-circle text-amber-600 hover:bg-amber-50 transition-colors" title="{{ __('messages.edit') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('users.gurus.destroy', $guru) }}" class="inline m-0">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost btn-xs btn-circle text-rose-600 hover:bg-rose-50 transition-colors" onclick="return confirm('Delete?')" title="{{ __('messages.delete') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-8 text-slate-400 font-medium">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-8">{{ $gurus->links() }}</div>
</x-app-layout>
