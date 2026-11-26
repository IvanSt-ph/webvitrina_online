@push('meta')
    <meta name="description" content="{{ $documentSummary }}">
    <link rel="canonical" href="{{ route($documentRoute) }}">
    <meta property="og:title" content="{{ $documentTitle }} — {{ config('app.name', 'WebVitrina') }}">
    <meta property="og:description" content="{{ $documentSummary }}">
    <meta property="og:url" content="{{ route($documentRoute) }}">
@endpush
