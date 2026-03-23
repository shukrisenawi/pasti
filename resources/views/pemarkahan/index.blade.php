<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-lg font-bold">{{ __('messages.pemarkahan') }}</h2>
            @if($isGuruOnly)
                <p class="text-sm text-slate-500">{{ $pastiName ?? '-' }}</p>
            @endif
        </div>
    </x-slot>

    @if($isGuruOnly)
        {{-- Mobile View --}}
        <div class="grid grid-cols-1 gap-4 md:hidden">
            @forelse($scores as $score)
                <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-50 flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-4">
                        <div class="pr-2">
                             <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 leading-none mb-1.5">{{ $score->year }}</p>
                             <h3 class="font-extrabold text-slate-900 leading-tight">{{ $score->titleOption?->title ?? '-' }}</h3>
                        </div>
                        <div class="text-right">
                             <div class="text-[10px] font-bold text-primary uppercase leading-none mb-1">Skor</div>
                             <div class="text-2xl font-black text-primary">{{ number_format((float) $score->score, 2) }}</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 text-xs text-slate-400 mt-2 border-t border-slate-50 pt-3">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                         <span>{{ __('messages.updated_at_label') }}: {{ $score->updated_at?->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            @empty
                <div class="text-center py-10 bg-white rounded-2xl border-2 border-dashed border-slate-100 text-slate-400 font-medium font-manrope">
                    {{ __('messages.no_records_found') }}
                </div>
            @endforelse
        </div>

        {{-- Desktop View --}}
        <div class="table-wrap hidden md:block">
            <table class="table-base">
                <thead>
                <tr>
                    <th>{{ __('messages.title') }}</th>
                    <th>{{ __('messages.year') }}</th>
                    <th class="text-center">{{ __('messages.total_score') }}</th>
                    <th class="text-right">{{ __('messages.updated_at_label') }}</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                @forelse($scores as $score)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="font-semibold text-slate-700">{{ $score->titleOption?->title ?? '-' }}</td>
                        <td class="text-slate-500 font-medium">{{ $score->year }}</td>
                        <td class="text-center font-bold text-primary">{{ number_format((float) $score->score, 2) }}</td>
                        <td class="text-right text-slate-400 text-sm font-medium">{{ $score->updated_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-8 text-slate-400">-</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="mb-4 flex flex-wrap gap-2">
            <a
                href="{{ route('pemarkahan.index', ['tab' => 'scores', 'title_option_id' => $selectedTitleOptionId ?: null, 'year' => $selectedYear]) }}"
                class="btn {{ $activeTab === 'scores' ? 'btn-primary' : 'btn-outline' }}"
            >
                {{ __('messages.total_score') }}
            </a>
            @role('master_admin')
                <a
                    href="{{ route('pemarkahan.index', ['tab' => 'title-options', 'title_option_id' => $selectedTitleOptionId ?: null, 'year' => $selectedYear]) }}"
                    class="btn {{ $activeTab === 'title-options' ? 'btn-primary' : 'btn-outline' }}"
                >
                    {{ __('messages.add_pemarkahan_title_option') }}
                </a>
            @endrole
        </div>

        <div class="card">
            <form method="GET" action="{{ route('pemarkahan.index') }}" class="grid gap-4 md:grid-cols-3 md:items-end">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div>
                    <label class="label-base">{{ __('messages.title') }}</label>
                    <select class="input-base" name="title_option_id" required>
                        <option value="">-- {{ __('messages.select') }} --</option>
                        @foreach($titleOptions as $option)
                            <option value="{{ $option->id }}" @selected((int) $selectedTitleOptionId === (int) $option->id)>{{ $option->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.year') }}</label>
                    <input class="input-base" type="number" name="year" min="2000" max="2100" value="{{ $selectedYear }}" required>
                </div>
                <div>
                    <button class="btn btn-outline">{{ __('messages.view') }}</button>
                </div>
            </form>
        </div>

        @if($activeTab === 'title-options')
            @role('master_admin')
                <div class="card mt-4">
                    <h3 class="text-base font-bold">{{ __('messages.add_pemarkahan_title_option') }}</h3>
                    <form method="POST" action="{{ route('pemarkahan.title-options.store') }}" class="mt-3 flex flex-wrap gap-2">
                        @csrf
                        <input class="input-base max-w-md" name="title" placeholder="{{ __('messages.title') }}" required>
                        <button class="btn btn-outline">{{ __('messages.add') }}</button>
                    </form>
                </div>
            @endrole
        @endif

        @if($activeTab === 'scores' && $pastis->isNotEmpty() && $selectedTitleOptionId)
            <div class="card mt-4">
                <form method="POST" action="{{ route('pemarkahan.store') }}">
                    @csrf
                    <input type="hidden" name="title_option_id" value="{{ $selectedTitleOptionId }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">

                    <div class="table-wrap">
                        <table class="table-base">
                            <thead>
                            <tr>
                                <th>{{ __('messages.name') }}</th>
                                <th>{{ __('messages.kawasan') }}</th>
                                <th>{{ __('messages.total_score') }}</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                            @foreach($pastis as $pasti)
                                <tr>
                                    <td>{{ $pasti->name }}</td>
                                    <td>{{ $pasti->kawasan?->name ?? '-' }}</td>
                                    <td>
                                        <input
                                            class="input-base"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="scores[{{ $pasti->id }}]"
                                            value="{{ old('scores.' . $pasti->id, $existingScores[$pasti->id] ?? '') }}"
                                            placeholder="0.00"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary">{{ __('messages.save') }}</button>
                    </div>
                </form>
            </div>
        @endif
    @endif
</x-app-layout>
