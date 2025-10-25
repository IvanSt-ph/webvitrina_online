{{-- подключатель для <x-buyer-layout> --}}
@include('layouts.buyer-layout', ['title' => $title ?? null, 'slot' => $slot])
