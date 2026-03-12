<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.leave_notice') }}</h2>
            @role('guru')
                <a href="{{ route('leave-notices.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
            @endrole
        </div>
    </x-slot>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
            <tr>
                @if($showAdminColumns)
                    <th>{{ __('messages.name') }}</th>
                    <th>{{ __('messages.pasti') }}</th>
                @endif
                <th>{{ __('messages.leave_date') }}</th>
                <th>{{ __('messages.leave_until') }}</th>
                <th>{{ __('messages.reason') }}</th>
                <th>{{ __('messages.mc_attachment') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($leaveNotices as $notice)
                <tr>
                    @if($showAdminColumns)
                        <td>{{ $notice->guru?->display_name ?? '-' }}</td>
                        <td>{{ $notice->guru?->pasti?->name ?? '-' }}</td>
                    @endif
                    <td>{{ $notice->leave_date?->format('d/m/Y') }}</td>
                    <td>{{ ($notice->leave_until ?? $notice->leave_date)?->format('d/m/Y') }}</td>
                    <td class="max-w-sm whitespace-pre-wrap">{{ $notice->reason }}</td>
                    <td>
                        @if($notice->mc_image_url)
                            <a href="{{ $notice->mc_image_url }}" target="_blank" class="btn btn-outline btn-sm">{{ __('messages.view') }}</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @php
                            $canDeleteNotice = $canDeleteAll
                                || ($currentGuruId && (int) $currentGuruId === (int) $notice->guru_id)
                                || (!empty($assignedPastiIds) && in_array((int) ($notice->guru?->pasti_id ?? 0), $assignedPastiIds, true));
                        @endphp
                        @if($canDeleteNotice)
                            <form method="POST" action="{{ route('leave-notices.destroy', $notice) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-error btn-sm" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
                            </form>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $showAdminColumns ? 7 : 5 }}" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $leaveNotices->links() }}</div>
</x-app-layout>
