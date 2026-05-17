<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Message;
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
        $conversations = $this->conversationQuery($request)
            ->paginate(20);

        return view('chats.index', compact('conversations'));
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

        return redirect()->route('product.show', [
            'identifier' => $product->slug,
            'chat' => $conversation->id,
        ]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $conversation->load(['buyer', 'seller']);
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
            'conversations'
        ));
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
            ->with(['buyer', 'seller', 'product', 'lastMessage'])
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->where('sender_id', '!=', $user->id)
                    ->whereNull('read_at'),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at');
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->includes($request->user()), 404);
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
