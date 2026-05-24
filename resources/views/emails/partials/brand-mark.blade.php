@php
    $appUrl = rtrim((string) config('app.url'), '/');
    $isLocalMailUrl = preg_match('/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/i', $appUrl) === 1;
    $logoUrl = asset('images/icon.png');
@endphp

@if (! $isLocalMailUrl)
    <img src="{{ $logoUrl }}"
         alt="WebVitrina"
         width="40"
         height="40"
         style="display:block;height:40px;width:40px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.25);">
@else
    <div style="height:40px;width:40px;border-radius:12px;background:#ffffff;color:#4f46e5;
                font-size:16px;font-weight:900;line-height:40px;text-align:center;
                box-shadow:0 4px 12px rgba(0,0,0,0.25);letter-spacing:-0.04em;">
        WV
    </div>
@endif
