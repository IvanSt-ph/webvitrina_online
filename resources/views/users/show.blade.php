@php
    $profileLayout = auth()->check()
        ? (auth()->user()->isSeller() ? 'seller-layout' : (auth()->user()->isBuyer() ? 'buyer-layout' : 'app-layout'))
        : 'app-layout';
@endphp

<x-dynamic-component :component="$profileLayout" :title="$user->name">
    @include('users.partials.profile-content')
</x-dynamic-component>
