<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.kelas') }}</h2>
            <a href="{{ route('kelas.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
        </div>
    </x-slot>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.pasti') }}</th>
                <th>{{ __('messages.student_count') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($kelasCollection as $kelas)
                <tr>
                    <td>{{ $kelas->name }}</td>
                    <td>{{ $kelas->pasti?->name }}</td>
                    <td>
                        <form method="POST" action="{{ route('kelas.student-count.update', $kelas) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label class="label-base">{{ __('messages.male') }}</label>
                                <input type="number" min="0" name="lelaki_count" value="{{ $kelas->studentCount?->lelaki_count ?? 0 }}" class="input-base w-24">
                            </div>
                            <div>
                                <label class="label-base">{{ __('messages.female') }}</label>
                                <input type="number" min="0" name="perempuan_count" value="{{ $kelas->studentCount?->perempuan_count ?? 0 }}" class="input-base w-24">
                            </div>
                            <button class="btn btn-outline">{{ __('messages.save') }}</button>
                        </form>
                    </td>
                    <td class="space-x-2">
                        <a href="{{ route('kelas.edit', $kelas) }}" class="btn btn-outline">{{ __('messages.edit') }}</a>
                        <form method="POST" action="{{ route('kelas.destroy', $kelas) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-error" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $kelasCollection->links() }}</div>
</x-app-layout>
