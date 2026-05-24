<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatImageService;
use App\Services\UserTrustService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    private const RECENT_MESSAGES_LIMIT = 80;

    public function __construct(
        private readonly ChatImageService $chatImages,
        private readonly UserTrustService $trustService
    ) {
    }

    public function index(Request $request)
    {
        $mode = $this->mode($request);
        $conversations = $this->conversationQuery($request, $mode)->paginate(20)->withQueryString();

        return $this->renderInbox($request, $conversations, null, $mode);
    }

    public function show(Request $request, Conversation $conversation)
    {
        abort_if($conversation->admin_deleted_at, 404);

        $mode = $conversation->isSupport() ? Conversation::TYPE_SUPPORT : Conversation::TYPE_MARKETPLACE;
        $conversations = $this->conversationQuery($request, $mode)->paginate(20)->withQueryString();

        return $this->renderInbox($request, $conversations, $conversation, $mode);
    }

    public function startSupport(Request $request, User $user)
    {
        abort_if($user->role === 'admin', 422, 'С администратором нельзя открыть support-чат.');

        $data = $request->validate([
            'source_conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
        ]);

        $conversation = Conversation::firstOrCreate(
            [
                'buyer_id' => $user->id,
                'seller_id' => $request->user()->id,
                'context_key' => 'support:' . $user->id,
            ],
            [
                'conversation_type' => Conversation::TYPE_SUPPORT,
                'last_message_at' => now(),
            ]
        );
        if ($conversation->admin_deleted_at) {
            $conversation->forceFill(['admin_deleted_at' => null])->save();
        }

        if ($conversation->wasRecentlyCreated) {
            $this->addSystemMessage(
                $conversation,
                $request->user(),
                'Поддержка открыла отдельный чат. Здесь можно обсудить вопрос без вмешательства в диалог покупателя и продавца.'
            );
        }

        if (! empty($data['source_conversation_id'])) {
            $sourceConversation = Conversation::with(['buyer', 'seller.shop', 'product'])
                ->find($data['source_conversation_id']);

            if ($sourceConversation?->isMarketplace()) {
                $subject = $sourceConversation->product
                    ? 'Товар: ' . $sourceConversation->product->title
                    : 'Общий marketplace-диалог';

                $this->addSystemMessage(
                    $conversation,
                    $request->user(),
                    "Поддержка открыла этот чат по обращению из диалога #{$sourceConversation->id}.\n"
                        . $subject . "\n"
                        . 'Покупатель: ' . ($sourceConversation->buyer?->name ?? 'Пользователь удалён') . "\n"
                        . 'Продавец: ' . ($sourceConversation->seller?->shop?->name ?? $sourceConversation->seller?->name ?? 'Продавец удалён') . "\n"
                        . 'Опишите здесь детали обращения, решение или следующий шаг.',
                    $sourceConversation
                );
            }
        }
        $this->restoreUserSideAfterAdminMessage($conversation);

        return redirect()->route('admin.chats.show', $conversation);
    }

    public function store(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->isSupport(), 403);
        abort_if($conversation->isLocked(), 423, 'Диалог заблокирован.');

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:max_width=8000,max_height=8000',
            ],
        ]);

        $body = trim((string) ($data['body'] ?? ''));

        if ($body === '' && ! $request->hasFile('image')) {
            throw ValidationException::withMessages([
                'body' => 'Напишите сообщение или прикрепите фото.',
            ]);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $body,
            'image_path' => $request->hasFile('image')
                ? $this->chatImages->upload($request->file('image'))
                : null,
        ]);

        $conversation->update(['last_message_at' => now()]);
        $this->restoreUserSideAfterAdminMessage($conversation);

        if ($request->expectsJson()) {
            return response()->json([
                'html' => $this->renderMessages($conversation, collect([$message->load('sender')])),
                'latest_message_id' => $message->id,
            ], 201);
        }

        return redirect()
            ->route('admin.chats.show', $conversation)
            ->with('success', 'Сообщение отправлено.');
    }

    public function destroy(Request $request, Conversation $conversation)
    {
        $conversation->update(['admin_deleted_at' => now()]);

        return redirect()
            ->route('admin.chats.index', ['mode' => $conversation->isSupport() ? Conversation::TYPE_SUPPORT : Conversation::TYPE_MARKETPLACE])
            ->with('success', 'Диалог скрыт из админского списка.');
    }

    public function system(Request $request, Conversation $conversation)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $message = $this->addSystemMessage($conversation, $request->user(), trim($data['body']));

        if ($request->expectsJson()) {
            return response()->json([
                'html' => $this->renderMessages($conversation, collect([$message->load('sender')])),
                'latest_message_id' => $message->id,
            ], 201);
        }

        return redirect()
            ->route('admin.chats.show', $conversation)
            ->with('success', 'Системное уведомление добавлено.');
    }

    public function note(Request $request, Conversation $conversation)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'type' => Message::TYPE_INTERNAL_NOTE,
            'body' => trim($data['body']),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'html' => $this->renderMessages($conversation, collect([$message->load('sender')])),
                'latest_message_id' => $message->id,
            ], 201);
        }

        return redirect()
            ->route('admin.chats.show', $conversation)
            ->with('success', 'Внутренняя заметка добавлена.');
    }

    public function lock(Request $request, Conversation $conversation)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = trim((string) ($data['reason'] ?? ''));

        $conversation->update([
            'locked_at' => now(),
            'locked_by' => $request->user()->id,
            'locked_reason' => $reason !== '' ? $reason : null,
            'last_message_at' => now(),
        ]);

        $this->addSystemMessage(
            $conversation,
            $request->user(),
            $reason !== ''
                ? 'Диалог временно заблокирован поддержкой. Причина: ' . $reason
                : 'Диалог временно заблокирован поддержкой.'
        );

        return redirect()
            ->route('admin.chats.show', $conversation)
            ->with('success', 'Диалог заблокирован.');
    }

    public function unlock(Request $request, Conversation $conversation)
    {
        $conversation->update([
            'locked_at' => null,
            'locked_by' => null,
            'locked_reason' => null,
            'last_message_at' => now(),
        ]);

        $this->addSystemMessage($conversation, $request->user(), 'Поддержка разблокировала диалог.');

        return redirect()
            ->route('admin.chats.show', $conversation)
            ->with('success', 'Диалог разблокирован.');
    }

    public function image(Request $request, Conversation $conversation, Message $message)
    {
        abort_unless($message->conversation_id === $conversation->id, 404);
        abort_unless($message->image_path && Storage::disk('local')->exists($message->image_path), 404);

        return response()->file(
            Storage::disk('local')->path($message->image_path),
            [
                'Content-Type' => 'image/webp',
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function renderInbox(Request $request, $conversations, ?Conversation $selectedConversation, string $mode)
    {
        $messages = collect();

        if ($selectedConversation) {
            $selectedConversation->messages()
                ->where('sender_id', '!=', $request->user()->id)
                ->whereNull('admin_read_at')
                ->update(['admin_read_at' => now()]);

            if ($selectedConversation->isSupport()) {
                $selectedConversation->messages()
                    ->where('sender_id', '!=', $request->user()->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
            }

            $selectedConversation->load(['buyer', 'seller.shop', 'lockedBy', 'product', 'messages.sender']);
            $messages = $selectedConversation->recentMessages(self::RECENT_MESSAGES_LIMIT, includeInternalNotes: true);
        }

        $totals = [
            'support' => Conversation::where('conversation_type', Conversation::TYPE_SUPPORT)->whereNull('admin_deleted_at')->count(),
            'marketplace' => Conversation::where('conversation_type', Conversation::TYPE_MARKETPLACE)->whereNull('admin_deleted_at')->count(),
            'support_unread' => $this->unreadConversationCount($request, Conversation::TYPE_SUPPORT),
            'marketplace_unread' => $this->unreadConversationCount($request, Conversation::TYPE_MARKETPLACE),
            'locked' => Conversation::whereNotNull('locked_at')->whereNull('admin_deleted_at')->count(),
            'today' => Conversation::whereDate('last_message_at', today())->whereNull('admin_deleted_at')->count(),
        ];

        $trustUsers = collect($conversations->items())
            ->flatMap(fn (Conversation $conversation) => [$conversation->buyer, $conversation->seller]);

        if ($selectedConversation) {
            $trustUsers = $trustUsers->push($selectedConversation->buyer, $selectedConversation->seller);
        }

        $trustProfiles = $this->trustService->profilesFor($trustUsers);

        return view('admin.chats.index', compact('conversations', 'selectedConversation', 'messages', 'totals', 'mode', 'trustProfiles'));
    }

    private function conversationQuery(Request $request, string $mode)
    {
        $search = trim((string) $request->query('q', ''));
        $type = $request->query('type');

        return Conversation::query()
            ->with(['buyer', 'seller.shop', 'product', 'lastMessage.sender', 'lockedBy'])
            ->withCount([
                'messages',
                'messages as unread_count' => fn ($query) => $query
                    ->where('sender_id', '!=', $request->user()->id)
                    ->whereNull('admin_read_at'),
                'messages as internal_notes_count' => fn ($query) => $query->where('type', Message::TYPE_INTERNAL_NOTE),
                'messages as system_events_count' => fn ($query) => $query->where('type', Message::TYPE_SYSTEM),
            ])
            ->where('conversation_type', $mode)
            ->whereNull('admin_deleted_at')
            ->when($mode === Conversation::TYPE_MARKETPLACE && $type === 'product', fn ($query) => $query->whereNotNull('product_id'))
            ->when($mode === Conversation::TYPE_MARKETPLACE && $type === 'general', fn ($query) => $query->whereNull('product_id'))
            ->when($type === 'locked', fn ($query) => $query->whereNotNull('locked_at'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->whereHas('buyer', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('seller', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('seller.shop', fn ($shopQuery) => $shopQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('messages', fn ($messageQuery) => $messageQuery->where('body', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('unread_count')
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at');
    }

    private function unreadConversationCount(Request $request, string $mode): int
    {
        return Conversation::query()
            ->where('conversation_type', $mode)
            ->whereNull('admin_deleted_at')
            ->whereHas('messages', fn ($query) => $query
                ->where('sender_id', '!=', $request->user()->id)
                ->whereNull('admin_read_at'))
            ->count();
    }

    private function addSystemMessage(Conversation $conversation, User $admin, string $body, ?Conversation $relatedConversation = null): Message
    {
        $message = $conversation->messages()->create([
            'sender_id' => $admin->id,
            'type' => Message::TYPE_SYSTEM,
            'related_conversation_id' => $relatedConversation?->id,
            'body' => $body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    private function restoreUserSideAfterAdminMessage(Conversation $conversation): void
    {
        if ($conversation->isSupport()) {
            $conversation->forceFill(['buyer_deleted_at' => null])->save();
        }
    }

    private function renderMessages(Conversation $conversation, $messages): string
    {
        return view('chats.partials.messages', [
            'conversation' => $conversation,
            'messages' => $messages,
            'imageRouteName' => 'admin.chats.messages.image',
            'showSenderLabels' => true,
            'showInternalNotes' => true,
        ])->render();
    }

    private function mode(Request $request): string
    {
        return $request->query('mode') === Conversation::TYPE_MARKETPLACE
            ? Conversation::TYPE_MARKETPLACE
            : Conversation::TYPE_SUPPORT;
    }
}
