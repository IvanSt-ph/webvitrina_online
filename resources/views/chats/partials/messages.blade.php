@foreach($messages as $message)
    @php
        $mine = $message->sender_id === auth()->id();
    @endphp
    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}"
         @if($mine) data-message-id="{{ $message->id }}" @endif>
        <div class="max-w-[84%] rounded-[1.4rem] px-4 py-3 shadow-sm
                    {{ $mine ? 'rounded-br-md bg-indigo-600 text-white' : 'rounded-bl-md border border-slate-200 bg-white text-slate-800' }}">
            @if($message->image_path)
                <a href="{{ route('chats.messages.image', [$conversation, $message]) }}"
                   target="_blank"
                   rel="noopener"
                   class="mb-2 block overflow-hidden rounded-2xl bg-black/5">
                    <img src="{{ route('chats.messages.image', [$conversation, $message]) }}"
                         alt="Фото в сообщении"
                         loading="lazy"
                         class="max-h-80 w-full object-cover">
                </a>
            @endif
            @if($message->body !== '')
                <div class="whitespace-pre-wrap break-words text-sm leading-6">{{ $message->body }}</div>
            @endif
            <div class="mt-1 flex items-center gap-1.5 text-[11px] {{ $mine ? 'text-indigo-100' : 'text-slate-400' }}">
                <span>
                    {{ $message->created_at->isToday() ? 'Сегодня, ' . $message->created_at->format('H:i') : $message->created_at->format('d.m H:i') }}
                </span>
                @if($mine)
                    <span class="wv-read-status {{ $message->read_at ? 'is-read' : '' }}"
                          data-read-status
                          title="{{ $message->read_at ? 'Прочитано' : 'Отправлено' }}"
                          aria-label="{{ $message->read_at ? 'Прочитано' : 'Отправлено' }}">
                        <span></span>
                        <span></span>
                    </span>
                @endif
            </div>
        </div>
    </div>
@endforeach
