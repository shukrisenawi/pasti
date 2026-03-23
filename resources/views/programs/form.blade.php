<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ $program->exists ? __('messages.edit') : __('messages.new') }} {{ __('messages.programs') }}</h2>
    </x-slot>

    @php
        $programFormRoute = $program->exists ? route('programs.edit', ['program' => $program]) : route('programs.create');
        $activeTab = $activeTab ?? 'program';
    @endphp

    @role('master_admin')
        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ $programFormRoute }}?tab=program" class="btn {{ $activeTab === 'program' ? 'btn-primary' : 'btn-outline' }}">
                {{ __('messages.programs') }}
            </a>
            <a href="{{ $programFormRoute }}?tab=title-options" class="btn {{ $activeTab === 'title-options' ? 'btn-primary' : 'btn-outline' }}">
                {{ __('messages.add_program_title_option') }}
            </a>
        </div>
    @endrole

    @if($activeTab === 'program')
        <div class="card">
            <form method="POST" action="{{ $program->exists ? route('programs.update', $program) : route('programs.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @if($program->exists)
                    @method('PUT')
                @endif

                @php($teacherScope = old('teacher_scope', $defaultTeacherScope ?? 'all'))
                @php($matchedTitleOption = $titleOptions->firstWhere('title', $program->title))
                @php($initialTitleOption = old('title_option', $matchedTitleOption?->id ? (string) $matchedTitleOption->id : ($program->exists ? 'other' : '')))
                @php($initialTitleOther = old('title_other', $matchedTitleOption ? '' : ($program->title ?? '')))

                @php($marksMap = $titleOptions->pluck('markah', 'id')->toArray())

                <div class="grid gap-4 md:grid-cols-2"
                    x-data="{
                        teacherScope: '{{ $teacherScope }}',
                        titleOption: '{{ $initialTitleOption }}',
                        marksMap: @js($marksMap),
                        mark: {{ old('markah', $program->markah ?? 1) }},
                        updateMark() {
                            if (this.titleOption && this.titleOption !== 'other' && this.marksMap[this.titleOption]) {
                                this.mark = this.marksMap[this.titleOption];
                            }
                        }
                    }"
                    x-init="$watch('titleOption', value => updateMark())"
                >
                    <div>
                        <label class="label-base">{{ __('messages.title') }}</label>
                        <select class="input-base" name="title_option" x-model="titleOption" required>
                            <option value="">-- {{ __('messages.select') }} --</option>
                            @foreach($titleOptions as $option)
                                <option value="{{ $option->id }}">{{ $option->title }}</option>
                            @endforeach
                            <option value="other">{{ __('messages.other') }}</option>
                        </select>
                        @error('title_option')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div x-show="titleOption === 'other'" x-cloak>
                        <label class="label-base">{{ __('messages.other_title') }}</label>
                        <input class="input-base" name="title_other" value="{{ $initialTitleOther }}">
                        @error('title_other')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label-base">{{ __('messages.date') }}</label>
                        <input class="input-base" type="date" name="program_date" value="{{ old('program_date', $program->program_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
                    </div>
                    <div>
                        <label class="label-base">{{ __('messages.time') }}</label>
                        <input class="input-base" type="time" name="program_time" value="{{ old('program_time', $program->program_time?->format('H:i')) }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="label-base">{{ __('messages.location') }}</label>
                        <input class="input-base" name="location" value="{{ old('location', $program->location ?? 'Kompleks PAS Sg Pau') }}" onfocus="this.select()">
                    </div>
                    <div class="md:col-span-2">
                        <label class="label-base">{{ __('messages.description') }}</label>
                        <textarea class="input-base" name="description" rows="3">{{ old('description', $program->description) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="label-base">{{ __('messages.program_banner') }}</label>
                        <input class="input-base" type="file" name="banner_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        @error('banner_image')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                        @if($program->banner_url)
                            <img src="{{ $program->banner_url }}" alt="{{ $program->title }}" class="mt-3 h-40 w-full rounded-2xl border border-slate-200 object-cover md:w-96">
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <label class="label-base">{{ __('messages.require_absence_reason') }}</label>
                        <label class="mt-2 inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="require_absence_reason"
                                value="1"
                                class="checkbox checkbox-sm"
                                @checked((bool) old('require_absence_reason', $program->require_absence_reason))
                            >
                            <span class="text-sm text-slate-700">{{ __('messages.require_absence_reason_hint') }}</span>
                        </label>
                    </div>
                    <div>
                        <label class="label-base">{{ __('messages.markah') }} (1-5)</label>
                        <input class="input-base" type="number" name="markah" min="1" max="5" x-model="mark" required>
                        @error('markah')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="label-base">{{ __('messages.teachers') }}</label>
                        <div class="space-y-3">
                            <select name="teacher_scope" class="input-base" x-model="teacherScope">
                                <option value="all">{{ __('messages.program_teacher_scope_all') }}</option>
                                <option value="selected">{{ __('messages.program_teacher_scope_selected') }}</option>
                            </select>
                            @error('teacher_scope')
                                <p class="text-sm text-rose-600">{{ $message }}</p>
                            @enderror

                            <div class="alert alert-info" x-show="teacherScope === 'all'">
                                <span>{{ __('messages.program_all_gurus_notice') }}</span>
                            </div>

                            <div x-show="teacherScope === 'selected'" x-cloak>
                                <select class="input-base" name="guru_ids[]" multiple size="8">
                                    @foreach($gurus as $guru)
                                        <option value="{{ $guru->id }}" @selected(in_array($guru->id, old('guru_ids', $selectedGuruIds ?? []), true))>{{ $guru->display_name }} ({{ $guru->pasti?->name ?? '-' }})</option>
                                    @endforeach
                                </select>
                                @error('guru_ids')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                                @error('guru_ids.*')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button class="btn btn-primary">{{ __('messages.save') }}</button>
                    <a href="{{ route('programs.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
                </div>
            </form>
        </div>
    @endif

    @if($activeTab === 'title-options')
        @role('master_admin')
            <div class="card mt-4">
                <h3 class="text-base font-bold">{{ __('messages.add_program_title_option') }}</h3>
                <form method="POST" action="{{ route('program-title-options.store') }}" class="mt-3 flex flex-wrap gap-2">
                    @csrf
                    <input class="input-base max-w-md" name="title" placeholder="{{ __('messages.title') }}" required>
                    <input class="input-base w-24" type="number" name="markah" placeholder="{{ __('messages.markah') }}" min="1" max="5" value="1" required>
                    <button class="btn btn-outline">{{ __('messages.add') }}</button>
                </form>
            </div>
        @endrole
    @endif
</x-app-layout>
