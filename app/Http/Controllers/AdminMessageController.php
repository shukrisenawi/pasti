<?php

namespace App\Http\Controllers;

use App\Models\AdminMessage;
use App\Models\AdminMessageRecipient;
use App\Models\Guru;
use App\Models\User;
use App\Notifications\AdminMessageReceivedNotification;
use App\Notifications\AdminMessageReplyNotification;
use App\Services\N8nWebhookService;
use App\Support\ConversationMessageFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminMessageController extends Controller
{
    public function __construct(
        private readonly ConversationMessageFormatter $conversationMessageFormatter,
        private readonly N8nWebhookService $n8nWebhookService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = AdminMessage::query()
            ->with(['sender', 'recipientLinks', 'recipients', 'replies'])
            ->withCount(['recipients', 'replies'])
            ->withMax('replies', 'created_at');

        if (! $user->hasRole('master_admin')) {
            $query->where(function (Builder $builder) use ($user): void {
                $builder->where('sender_id', $user->id)
                    ->orWhereHas('recipientLinks', fn (Builder $q) => $q->where('user_id', $user->id));
            });
        }

        $messages = $query
            ->orderByRaw('COALESCE(replies_max_created_at, admin_messages.created_at) DESC')
            ->latest('id')
            ->paginate(10);

        return view('messages.index', [
            'messages' => $messages,
            'canCompose' => $user->hasAnyRole(['master_admin', 'admin', 'guru']),
            'isGuru' => $user->hasRole('guru'),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        return view('messages.form', [
            'gurus' => $this->availableGuruRecipients($user),
            'isGuru' => $user->hasRole('guru'),
            'isAdminComposer' => $user->hasAnyRole(['master_admin', 'admin']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        if ($user->hasAnyRole(['master_admin', 'admin'])) {
            return $this->storeFromAdmin($request, $user);
        }

        return $this->storeFromGuru($request, $user);
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

        AdminMessageRecipient::query()
            ->where('admin_message_id', $message->id)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereIn('type', [AdminMessageReceivedNotification::class, AdminMessageReplyNotification::class])
            ->where('data->admin_message_id', $message->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('messages.show', [
            'message' => $message,
            'canReply' => true,
            'canViewRecipients' => $user->hasAnyRole(['master_admin', 'admin']),
            'conversationEntries' => $this->conversationEntries($message),
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
            'body' => trim((string) ($data['body'] ?? '')),
            'image_path' => $request->hasFile('attachment')
                ? $request->file('attachment')->store('admin-message-replies', 'public')
                : null,
        ]);

        $message->loadMissing(['sender', 'recipients']);
        $reply->loadMissing('sender');

        $targets = $message->participants()
            ->where('id', '!=', $user->id)
            ->values();

        if ($targets->isNotEmpty()) {
            Notification::send($targets, new AdminMessageReplyNotification($message, $reply));
        }

        return redirect()->route('messages.show', $message)->with('status', __('messages.saved'));
    }

    private function ensureMessageAccessible(User $user, AdminMessage $message): void
    {
        if ($user->hasRole('master_admin')) {
            return;
        }

        if ((int) $message->sender_id === (int) $user->id) {
            return;
        }

        $isRecipient = AdminMessageRecipient::query()
            ->where('admin_message_id', $message->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isRecipient, 403);
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

    private function storeFromAdmin(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'conversation_type' => ['required', Rule::in(['direct', 'bulk'])],
            'body' => ['nullable', 'string', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip', 'max:10240', 'required_without:body'],
            'recipient_scope' => ['nullable', Rule::in(['all', 'selected'])],
            'recipient_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'recipient_user_ids' => ['array'],
            'recipient_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $recipientUsers = $data['conversation_type'] === 'direct'
            ? $this->availableRecipientUsersQuery($user)
                ->where('users.id', (int) ($data['recipient_user_id'] ?? 0))
                ->get()
            : $this->availableRecipientUsersQuery($user)
                ->when(
                    ($data['recipient_scope'] ?? 'all') === 'selected',
                    fn (Builder $query) => $query->whereIn('users.id', $data['recipient_user_ids'] ?? [])
                )
                ->get();

        if ($recipientUsers->isEmpty()) {
            return back()->withErrors([
                'recipient_user_ids' => 'Tiada guru penerima yang sah dipilih.',
            ])->withInput();
        }

        $sentToAll = $data['conversation_type'] === 'bulk' && ($data['recipient_scope'] ?? 'all') === 'all';
        $shouldApplyVariables = $data['conversation_type'] === 'bulk' && $recipientUsers->count() > 1;
        $message = AdminMessage::query()->create([
            'sender_id' => $user->id,
            'title' => $data['conversation_type'] === 'direct'
                ? 'Perbualan dengan ' . ($recipientUsers->first()?->display_name ?? 'guru')
                : ($sentToAll
                    ? 'Hebahan kepada semua guru'
                    : 'Hebahan kepada ' . $recipientUsers->count() . ' guru'),
            'body' => $shouldApplyVariables
                ? $this->conversationMessageFormatter->format($data['body'] ?? '', $user->fresh('guru.pasti'))
                : trim((string) ($data['body'] ?? '')),
            'image_path' => $request->hasFile('attachment')
                ? $request->file('attachment')->store('admin-messages', 'public')
                : null,
            'sent_to_all' => $sentToAll,
        ]);

        $message->recipientLinks()->createMany(
            $recipientUsers->map(fn (User $recipient) => ['user_id' => $recipient->id])->all()
        );

        Notification::send($recipientUsers, new AdminMessageReceivedNotification($message->load('sender', 'recipients')));

        if ($data['conversation_type'] === 'bulk') {
            $this->n8nWebhookService->send(
                sprintf(
                    '%s hantar hebahan kepada %d guru. Mesej: %s',
                    $user->display_name,
                    $recipientUsers->count(),
                    Str::limit($message->body, 120)
                ),
                $this->n8nWebhookService->toActionUrl(route('messages.show', $message))
            );
        }

        return redirect()->route('messages.show', $message)->with('status', __('messages.saved'));
    }

    private function storeFromGuru(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip', 'max:10240', 'required_without:body'],
        ]);

        $user->loadMissing('guru.pasti');
        $pastiId = (int) ($user->guru?->pasti_id ?? 0);
        abort_unless($pastiId > 0, 403);

        $masterAdmins = User::query()->role('master_admin')->get();
        $assignedAdmins = User::query()
            ->role('admin')
            ->whereHas('assignedPastis', fn (Builder $query) => $query->whereKey($pastiId))
            ->get();

        $recipientUsers = $masterAdmins->merge($assignedAdmins)
            ->unique('id')
            ->where('id', '!=', $user->id)
            ->values();

        if ($recipientUsers->isEmpty()) {
            return back()->withErrors([
                'body' => 'Tiada pentadbir yang boleh menerima perbualan ini.',
            ])->withInput();
        }

        $message = AdminMessage::query()->create([
            'sender_id' => $user->id,
            'title' => 'Perbualan ' . ($user->guru?->pasti?->name ?? 'PASTI'),
            'body' => trim((string) ($data['body'] ?? '')),
            'image_path' => $request->hasFile('attachment')
                ? $request->file('attachment')->store('admin-messages', 'public')
                : null,
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->createMany(
            $recipientUsers->map(fn (User $recipient) => ['user_id' => $recipient->id])->all()
        );

        Notification::send($recipientUsers, new AdminMessageReceivedNotification($message->load('sender', 'recipients')));

        $this->n8nWebhookService->sendGroup2(
            sprintf(
                '%s dari %s hantar mesej kepada admin. Mesej: %s',
                $user->display_name,
                $user->guru?->pasti?->name ?? 'PASTI',
                Str::limit($message->body, 120)
            ),
            $this->n8nWebhookService->toActionUrl(route('messages.show', $message))
        );

        return redirect()->route('messages.show', $message)->with('status', __('messages.saved'));
    }

    private function conversationEntries(AdminMessage $message): Collection
    {
        return collect([[
            'id' => 'message-' . $message->id,
            'sender' => $message->sender,
            'body' => $message->body,
            'attachment_url' => $message->attachment_url,
            'attachment_name' => $message->attachment_name,
            'is_image_attachment' => $message->is_image_attachment,
            'created_at' => $message->created_at,
        ]])->merge(
            $message->replies->map(function ($reply): array {
                return [
                    'id' => 'reply-' . $reply->id,
                    'sender' => $reply->sender,
                    'body' => $reply->body,
                    'attachment_url' => $reply->attachment_url,
                    'attachment_name' => $reply->attachment_name,
                    'is_image_attachment' => $reply->is_image_attachment,
                    'created_at' => $reply->created_at,
                ];
            })
        )->sortBy('created_at')->values();
    }
}
