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
                            <a href="{{ $notice->mc_image_url }}" target="_blank" class="btn btn-ghost btn-xs btn-circle text-primary" title="{{ __('messages.view') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.306 5 12 5c4.694 0 8.601 3.049 9.964 6.678a1.012 1.012 0 010 .644C20.601 15.951 16.694 19 12 19c-4.694 0-8.601-3.049-9.964-6.678z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
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
                            <form method="POST" action="{{ route('leave-notices.destroy', $notice) }}" class="inline m-0">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost btn-xs btn-circle text-rose-600" onclick="return confirm('Delete?')" title="{{ __('messages.delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
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
