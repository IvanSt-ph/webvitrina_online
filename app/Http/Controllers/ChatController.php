<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    private const RECENT_MESSAGES_LIMIT = 50;

    public function __construct(private readonly ChatImageService $chatImages)
    {
    }

    public function index(Request $request)
    {
        $query = $this->conversationQuery($request);
        $selectedConversationId = $request->integer('chat');

        $conversations = (clone $query)
            ->paginate(20);

        $selectedConversation = $selectedConversationId
            ? (clone $query)->whereKey($selectedConversationId)->first()
            : $conversations->getCollection()->first();

        $selectedMessages = collect();

        if ($selectedConversation) {
            $selectedConversation->load(['buyer', 'seller', 'product', 'order']);
            $selectedConversation->messages()
                ->where('sender_id', '!=', $request->user()->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $selectedMessages = $selectedConversation->recentMessages(self::RECENT_MESSAGES_LIMIT);
        }

        return view('chats.index', compact('conversations', 'selectedConversation', 'selectedMessages'));
    }

    public function start(Request $request, Shop $shop)
    {
        $seller = $shop->user;
        $user = $request->user();

        abort_unless($seller && $seller->isSeller(), 404);
        abort_unless($user->isBuyer() || $user->isSeller(), 403);
        abort_if($seller->is($user), 403, 'Нельзя начать чат с самим собой.');

        $conversation = Conversation::firstOrCreate([
            'buyer_id' => $user->id,
            'seller_id' => $seller->id,
            'product_id' => null,
            'context_key' => Conversation::generalContextKey(),
        ]);
        $this->restoreForUser($conversation, $user);

        return redirect()->route('seller.show', [
            'identifier' => $shop->slug,
            'chat' => $conversation->id,
        ]);
    }

    public function startForProduct(Request $request, Product $product)
    {
        $seller = $product->seller;
        $user = $request->user();

        abort_unless($product->status === 'active', 404);
        abort_unless($seller && $seller->isSeller(), 404);
        abort_unless($user->isBuyer() || $user->isSeller(), 403);
        abort_if($seller->is($user), 403, 'Нельзя начать чат с самим собой.');

        $conversation = Conversation::firstOrCreate([
            'buyer_id' => $user->id,
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'context_key' => Conversation::productContextKey($product),
        ]);
        $this->restoreForUser($conversation, $user);

        return redirect()->route('product.show', [
            'identifier' => $product->slug,
            'chat' => $conversation->id,
        ]);
    }

    public function startForOrderProduct(Request $request, Order $order, Product $product)
    {
        $user = $request->user();

        abort_unless($user->isBuyer() && $order->user_id === $user->id, 403);
        abort_unless($order->seller_id === $product->user_id, 404);
        abort_unless($order->items()->where('product_id', $product->id)->exists(), 404);

        $conversation = Conversation::firstOrCreate([
            'buyer_id' => $user->id,
            'seller_id' => $order->seller_id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'context_key' => Conversation::orderProductContextKey($order, $product),
        ]);
        $this->restoreForUser($conversation, $user);

        $contextBody = "Диалог по заказу {$order->number}.\nТовар: {$product->title}";

        if (! $conversation->messages()->where('type', Message::TYPE_SYSTEM)->where('body', $contextBody)->exists()) {
            $conversation->messages()->create([
                'sender_id' => $user->id,
                'type' => Message::TYPE_SYSTEM,
                'order_id' => $order->id,
                'body' => $contextBody,
            ]);
            $conversation->update(['last_message_at' => now()]);
            $this->restoreForOtherParticipant($conversation, $user);
        }

        return redirect()->route('chats.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $conversation->load(['buyer', 'seller', 'product', 'order']);
        $supportConversation = $this->supportConversationFor($request->user());
        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->recentMessages(self::RECENT_MESSAGES_LIMIT);
        $hasOlderMessages = $conversation->hasMoreThanRecentMessages(self::RECENT_MESSAGES_LIMIT);
        $oldestMessageId = $messages->first()?->id;
        $latestMessageId = $messages->last()?->id;
        $latestReadOutgoingMessageId = $conversation->latestReadOutgoingMessageIdFor($request->user());

        $conversations = $this->conversationQuery($request)
            ->limit(20)
            ->get();

        return view('chats.show', compact(
            'conversation',
            'messages',
            'hasOlderMessages',
            'oldestMessageId',
            'latestMessageId',
            'latestReadOutgoingMessageId',
            'conversations',
            'supportConversation'
        ));
    }

    public function support(Request $request)
    {
        $supportConversation = $this->supportConversationFor($request->user());
        $chatLayout = $request->user()->isSeller() ? 'seller-layout' : 'buyer-layout';

        return view('buyer.support.index', compact('supportConversation', 'chatLayout'));
    }

    public function startSupport(Request $request)
    {
        $data = $request->validate([
            'topic' => ['nullable', 'string', 'max:80'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $conversation = $this->ensureSupportConversation($request->user());
        $topic = trim((string) ($data['topic'] ?? ''));
        $details = trim((string) ($data['details'] ?? ''));

        if ($topic !== '' || $details !== '') {
            $body = 'Новое обращение в поддержку.';

            if ($topic !== '') {
                $body .= "\nТема: ".$topic;
            }

            if ($details !== '') {
                $body .= "\nПодробности: ".$details;
            }

            $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'type' => Message::TYPE_SYSTEM,
                'body' => $body,
            ]);

            $conversation->update(['last_message_at' => now()]);
            $this->restoreForOtherParticipant($conversation, $request->user());
        }

        return redirect()
            ->route('chats.show', $conversation)
            ->with('success', 'Support-чат открыт.');
    }

    public function startSupportForOrder(Request $request, Order $order)
    {
        $user = $request->user();

        abort_unless($user->isBuyer() && $order->user_id === $user->id, 403);

        $order->load(['seller.shop', 'items.product']);
        $conversation = $this->ensureSupportConversation($user);
        $shopName = $order->seller?->shop?->name ?? $order->seller?->name ?? 'Продавец не найден';
        $productTitles = $order->items
            ->map(fn ($item) => $item->product?->title ?? 'Товар удалён')
            ->join(', ');
        $body = "Обращение по заказу {$order->number}.\n"
            . "Магазин: {$shopName}\n"
            . "Товары: {$productTitles}\n"
            . 'Опишите проблему следующим сообщением.';

        if (! $conversation->messages()->where('type', Message::TYPE_SYSTEM)->where('body', $body)->exists()) {
            $conversation->messages()->create([
                'sender_id' => $user->id,
                'type' => Message::TYPE_SYSTEM,
                'order_id' => $order->id,
                'body' => $body,
            ]);
            $conversation->update(['last_message_at' => now()]);
            $this->restoreForOtherParticipant($conversation, $user);
        }

        return redirect()
            ->route('chats.show', $conversation)
            ->with('success', 'Обращение по заказу открыто. Опишите проблему в чате.');
    }

    public function openSupportFromConversation(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);
        abort_if($conversation->isSupport(), 422, 'Вы уже в support-чате.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:80'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $supportConversation = $this->ensureSupportConversation($request->user());
        $reason = trim($data['reason']);
        $details = trim((string) ($data['details'] ?? ''));
        $other = $conversation->otherParticipant($request->user());
        $initiatorRole = $request->user()->isSeller() ? 'Продавец' : 'Покупатель';
        $otherRole = $other->isSeller() ? 'Продавец' : 'Покупатель';

        $body = "Открыто обращение по диалогу #{$conversation->id}.\n"
            . 'Инициатор: ' . $request->user()->name . ' - ' . $initiatorRole . ".\n"
            . 'Вторая сторона: ' . $other->name . ' - ' . $otherRole . ".\n"
            . 'Причина: ' . $reason;

        if ($details !== '') {
            $body .= "\nПодробности: " . $details;
        }

        $supportConversation->messages()->create([
            'sender_id' => $request->user()->id,
            'type' => Message::TYPE_SYSTEM,
            'related_conversation_id' => $conversation->id,
            'body' => $body,
        ]);

        $supportConversation->update(['last_message_at' => now()]);
        $this->restoreForOtherParticipant($supportConversation, $request->user());

        return redirect()
            ->route('chats.show', $supportConversation)
            ->with('success', 'Обращение отправлено в поддержку.');
    }

    public function olderMessages(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $data = $request->validate([
            'before' => ['required', 'integer', 'min:1'],
        ]);

        $messages = $conversation->olderMessagesBefore((int) $data['before'], self::RECENT_MESSAGES_LIMIT);
        $oldestMessageId = $messages->first()?->id;

        return response()->json([
            'html' => view('chats.partials.messages', compact('conversation', 'messages'))->render(),
            'oldest_message_id' => $oldestMessageId,
            'has_older_messages' => $oldestMessageId
                ? $conversation->hasMessagesBefore($oldestMessageId)
                : false,
        ]);
    }

    public function newerMessages(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $data = $request->validate([
            'after' => ['required', 'integer', 'min:0'],
        ]);

        $messages = $conversation->newerMessagesAfter((int) $data['after'], self::RECENT_MESSAGES_LIMIT);
        $latestMessageId = $messages->last()?->id ?? (int) $data['after'];

        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->where('id', '<=', $latestMessageId)
            ->update(['read_at' => now()]);

        return response()->json([
            'html' => view('chats.partials.messages', compact('conversation', 'messages'))->render(),
            'latest_message_id' => $latestMessageId,
            'count' => $messages->count(),
            'latest_read_outgoing_message_id' => $conversation->latestReadOutgoingMessageIdFor($request->user()),
        ]);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);
        abort_if($conversation->isLocked(), 423, 'Диалог временно заблокирован поддержкой.');

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
        $this->restoreForOtherParticipant($conversation, $request->user());

        if ($request->expectsJson()) {
            $messages = collect([$message->load('sender')]);

            return response()->json([
                'html' => view('chats.partials.messages', compact('conversation', 'messages'))->render(),
                'latest_message_id' => $message->id,
                'latest_read_outgoing_message_id' => $conversation->latestReadOutgoingMessageIdFor($request->user()),
            ], 201);
        }

        $redirectTo = $request->string('redirect_to')->toString();

        if ($this->isSafeRedirectTarget($redirectTo)) {
            return redirect($redirectTo)->with('success', 'Сообщение отправлено.');
        }

        return redirect()
            ->route('chats.show', $conversation)
            ->with('success', 'Сообщение отправлено.');
    }

    public function destroy(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $column = $conversation->deletedColumnFor($request->user());
        abort_unless($column, 403);

        $conversation->update([$column => now()]);

        return redirect()
            ->route('chats.index')
            ->with('success', 'Диалог скрыт из вашего списка.');
    }

    public function image(Request $request, Conversation $conversation, Message $message)
    {
        $this->authorizeParticipant($request, $conversation);

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

    private function conversationQuery(Request $request)
    {
        $user = $request->user();

        return Conversation::query()
            ->where(fn ($query) => $query
                ->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id))
            ->where(function ($query) use ($user) {
                $query
                    ->where(fn ($subQuery) => $subQuery
                        ->where('buyer_id', $user->id)
                        ->whereNull('buyer_deleted_at'))
                    ->orWhere(fn ($subQuery) => $subQuery
                        ->where('seller_id', $user->id)
                        ->whereNull('seller_deleted_at'));
            })
            ->with(['buyer', 'seller', 'product', 'order', 'lastMessage'])
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->where('sender_id', '!=', $user->id)
                    ->whereNull('read_at'),
            ])
            ->orderByDesc('unread_count')
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at');
    }

    private function supportConversationFor(User $user): ?Conversation
    {
        return Conversation::query()
            ->where('conversation_type', Conversation::TYPE_SUPPORT)
            ->where('buyer_id', $user->id)
            ->with(['buyer', 'seller'])
            ->first();
    }

    private function ensureSupportConversation(User $user): Conversation
    {
        $admin = User::query()
            ->where('role', 'admin')
            ->orderBy('id')
            ->first();

        if (! $admin) {
            throw ValidationException::withMessages([
                'support' => 'Служба поддержки пока недоступна. Попробуйте позже.',
            ]);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'buyer_id' => $user->id,
                'seller_id' => $admin->id,
                'context_key' => 'support:' . $user->id,
            ],
            [
                'conversation_type' => Conversation::TYPE_SUPPORT,
                'last_message_at' => now(),
            ]
        );
        $this->restoreForUser($conversation, $user);

        if ($conversation->wasRecentlyCreated) {
            $conversation->messages()->create([
                'sender_id' => $admin->id,
                'type' => Message::TYPE_SYSTEM,
                'body' => 'Support-чат открыт. Опишите вопрос, спор или проблему — поддержка ответит здесь.',
            ]);
        }

        return $conversation;
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->includes($request->user()), 404);
        abort_if($conversation->isDeletedFor($request->user()), 404);
    }

    private function restoreForUser(Conversation $conversation, User $user): void
    {
        $column = $conversation->deletedColumnFor($user);

        if ($column && $conversation->{$column}) {
            $conversation->forceFill([$column => null])->save();
        }
    }

    private function restoreForOtherParticipant(Conversation $conversation, User $sender): void
    {
        $other = $conversation->otherParticipant($sender);

        if ($other->role === 'admin' && $conversation->admin_deleted_at) {
            $conversation->forceFill(['admin_deleted_at' => null])->save();
            return;
        }

        $this->restoreForUser($conversation, $other);
    }

    private function isSafeRedirectTarget(string $redirectTo): bool
    {
        if ($redirectTo === '') {
            return false;
        }

        if (Str::startsWith($redirectTo, ['/']) && ! Str::startsWith($redirectTo, ['//'])) {
            return true;
        }

        $target = parse_url($redirectTo);
        $app = parse_url(config('app.url'));

        if (! $target || ! $app) {
            return false;
        }

        return ($target['scheme'] ?? null) === ($app['scheme'] ?? null)
            && ($target['host'] ?? null) === ($app['host'] ?? null)
            && ($target['port'] ?? null) === ($app['port'] ?? null);
    }
}
