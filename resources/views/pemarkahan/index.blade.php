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
        <div class="table-wrap">
            <table class="table-base">
                <thead>
                <tr>
                    <th>{{ __('messages.title') }}</th>
                    <th>{{ __('messages.year') }}</th>
                    <th>{{ __('messages.total_score') }}</th>
                    <th>{{ __('messages.updated_at_label') }}</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($scores as $score)
                    <tr>
                        <td>{{ $score->titleOption?->title ?? '-' }}</td>
                        <td>{{ $score->year }}</td>
                        <td>{{ number_format((float) $score->score, 2) }}</td>
                        <td>{{ $score->updated_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">-</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="card">
            <form method="GET" action="{{ route('pemarkahan.index') }}" class="grid gap-4 md:grid-cols-3 md:items-end">
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

        @if($pastis->isNotEmpty() && $selectedTitleOptionId)
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
