<?php

namespace App\Http\Controllers;

use App\Models\AdminMessage;
use App\Models\AdminMessageRecipient;
use App\Models\Guru;
use App\Models\User;
use App\Notifications\AdminMessageReceivedNotification;
use App\Notifications\AdminMessageReplyNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminMessageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = AdminMessage::query()
            ->with(['sender', 'recipientLinks', 'replies'])
            ->withCount(['recipients', 'replies'])
            ->withMax('replies', 'created_at');

        if ($user->hasRole('guru')) {
            $query->whereHas('recipientLinks', fn ($q) => $q->where('user_id', $user->id));
        } elseif (! $user->hasRole('master_admin')) {
            $query->where('sender_id', $user->id);
        }

        $messages = $query
            ->orderByRaw('COALESCE(replies_max_created_at, admin_messages.created_at) DESC')
            ->latest('id')
            ->paginate(10);

        return view('messages.index', [
            'messages' => $messages,
            'canCompose' => $user->hasAnyRole(['master_admin', 'admin']),
            'isGuru' => $user->hasRole('guru'),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        return view('messages.form', [
            'gurus' => $this->availableGuruRecipients($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip', 'max:10240'],
            'recipient_scope' => ['required', Rule::in(['all', 'selected'])],
            'recipient_user_ids' => ['array'],
            'recipient_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $recipientUsers = $this->availableRecipientUsersQuery($user)
            ->when(
                $data['recipient_scope'] === 'selected',
                fn ($q) => $q->whereIn('users.id', $data['recipient_user_ids'] ?? [])
            )
            ->get();

        if ($recipientUsers->isEmpty()) {
            return back()->withErrors([
                'recipient_user_ids' => 'Tiada guru penerima yang sah dipilih.',
            ])->withInput();
        }

        $message = AdminMessage::query()->create([
            'sender_id' => $user->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'image_path' => $request->hasFile('attachment')
                ? $request->file('attachment')->store('admin-messages', 'public')
                : null,
            'sent_to_all' => $data['recipient_scope'] === 'all',
        ]);

        $message->recipientLinks()->createMany(
            $recipientUsers->map(fn (User $recipient) => ['user_id' => $recipient->id])->all()
        );

        Notification::send($recipientUsers, new AdminMessageReceivedNotification($message->load('sender')));

        return redirect()->route('messages.show', $message)->with('status', __('messages.saved'));
    }

    public function show(Request $request, AdminMessage $message): View
    {
        $user = $request->user();

        $this->ensureMessageAccessible($user, $message);

        $message->load([
            'sender',
            'recipients.guru.pasti',
            'recipientLinks',
            'replies.sender.guru.pasti',
        ]);

        if ($user->hasRole('guru')) {
            AdminMessageRecipient::query()
                ->where('admin_message_id', $message->id)
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('messages.show', [
            'message' => $message,
            'canReply' => true,
            'canViewRecipients' => $user->hasAnyRole(['master_admin', 'admin']),
        ]);
    }

    public function reply(Request $request, AdminMessage $message): RedirectResponse
    {
        $user = $request->user();

        $this->ensureMessageAccessible($user, $message);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip', 'max:10240', 'required_without:body'],
        ]);

        $reply = $message->replies()->create([
            'sender_id' => $user->id,
            'body' => (string) ($data['body'] ?? ''),
            'image_path' => $request->hasFile('attachment')
                ? $request->file('attachment')->store('admin-message-replies', 'public')
                : null,
        ]);

        $message->loadMissing(['sender', 'recipients']);
        $reply->loadMissing('sender');

        if ($user->hasRole('guru')) {
            if ($message->sender && $message->sender->id !== $user->id) {
                $message->sender->notify(new AdminMessageReplyNotification($message, $reply));
            }
        } else {
            $targets = $message->recipients->where('id', '!=', $user->id)->values();
            if ($targets->isNotEmpty()) {
                Notification::send($targets, new AdminMessageReplyNotification($message, $reply));
            }
        }

        return redirect()->route('messages.show', $message)->with('status', __('messages.saved'));
    }

    private function ensureMessageAccessible(User $user, AdminMessage $message): void
    {
        if ($user->hasRole('master_admin')) {
            return;
        }

        if ($user->hasRole('guru')) {
            $isRecipient = AdminMessageRecipient::query()
                ->where('admin_message_id', $message->id)
                ->where('user_id', $user->id)
                ->exists();

            abort_unless($isRecipient, 403);

            return;
        }

        abort_unless((int) $message->sender_id === (int) $user->id, 403);
    }

    private function availableRecipientUsersQuery(User $user)
    {
        return User::query()
            ->select('users.*')
            ->role('guru')
            ->where('users.id', '!=', $user->id)
            ->whereHas('guru', function ($q) use ($user) {
                $q->where('active', true)
                    ->where('is_assistant', false)
                    ->when(
                        ! $this->isMasterAdmin($user),
                        fn ($inner) => $inner->whereIn('pasti_id', $this->assignedPastiIds($user))
                    );
            });
    }

    private function availableGuruRecipients(User $user)
    {
        return Guru::query()
            ->with(['user', 'pasti'])
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $user->id)
            ->where('active', true)
            ->where('is_assistant', false)
            ->when(
                ! $this->isMasterAdmin($user),
                fn ($q) => $q->whereIn('pasti_id', $this->assignedPastiIds($user))
            )
            ->get()
            ->filter(fn (Guru $guru) => (bool) $guru->user)
            ->sortBy(fn (Guru $guru) => $guru->display_name)
            ->values();
    }
}
