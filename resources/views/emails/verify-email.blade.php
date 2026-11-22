@php
    /** @var \App\Models\User $user */
    $userName = trim((string) ($user->name ?? ''));
    $appUrl = rtrim((string) config('app.url'), '/');
@endphp
<!DOCTYPE html>
<html lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Подтверждение email — WebVitrina</title>
    <style>
        table { border-collapse:separate;border-spacing:0; mso-table-lspace:0pt; mso-table-rspace:0pt; }
        a { text-decoration:underline; }
        @media only screen and (max-width:680px) {
            .outer-pad { padding:12px 8px !important; }
            .hero-pad { padding:24px 20px !important; }
            .cta-icon { display:none !important; }
            .cta-inner { padding:24px 20px !important; }
            .link-icon { display:none !important; }
            .content-pad { padding-left:20px !important; padding-right:20px !important; }
            .stack { display:block !important; width:100% !important; box-sizing:border-box !important; }
            .hero-title { font-size:27px !important; line-height:34px !important; }
            .art { padding:24px 0 0 !important; }
            .stack { border-left:0 !important; padding-left:0 !important; }
            .cta-button { padding:20px 0 0 !important; text-align:left !important; }
            .benefit { padding:0 0 12px !important; }
            .header-tag { display:none !important; }
            .footer-links { padding-top:16px !important; text-align:left !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;width:100%;background-color:#F0EFFE;color:#23213F;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">Подтвердите адрес электронной почты, чтобы продолжить пользоваться WebVitrina.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#F0EFFE">
<tr><td align="center" class="outer-pad" style="padding:20px 2.3%;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" align="center" bgcolor="#FAFAFF" style="width:100%;table-layout:fixed;background-color:#FAFAFF;border-radius:22px;">
    <tr><td class="content-pad" bgcolor="#312E81" style="padding:22px 4.7%;background-color:#312E81;background-image:radial-gradient(ellipse at 68% 140%,#7664F2 0%,transparent 43%),linear-gradient(115deg,#24224F,#4F46E5);border-radius:22px 22px 16px 16px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
            <td width="68" valign="middle" style="width:68px;">
                <table role="presentation" width="44" cellpadding="0" cellspacing="0"><tr><td align="center" bgcolor="#FFFFFF" height="44" style="height:44px;border-radius:12px;color:#4F46E5;font-size:18px;font-weight:bold;">WV</td></tr></table>
            </td>
            <td valign="middle" style="color:#FFFFFF;"><p style="margin:0;font-size:25px;line-height:30px;font-weight:bold;">WebVitrina</p><p style="margin:4px 0 0;font-size:13px;line-height:19px;color:#E0E7FF;">Маркетплейс, где товары находят своих людей</p></td>
            <td class="header-tag" width="190" align="right" style="color:#FFFFFF;font-size:13px;line-height:18px;"><span style="display:inline-block;border:1px solid #BCB5FF;border-radius:24px;padding:9px 16px;">&#9993;&nbsp; Подтверждение email</span></td>
        </tr></table>
    </td></tr>
    <tr><td class="hero-pad" style="padding:24px 4.7% 24px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;"><tr>
            <td class="stack" width="62%" valign="middle" style="width:62%;">
                <p style="margin:0 0 16px;font-size:17px;line-height:26px;overflow-wrap:anywhere;word-wrap:break-word;">Привет@if($userName !== ''), <strong>{{ $userName }}</strong>@endif!</p>
                <h1 class="hero-title" style="margin:0 0 18px;font-size:34px;line-height:41px;letter-spacing:-0.7px;font-weight:bold;color:#23213F;">Подтвердите свой email, чтобы<br>открыть возможности WebVitrina</h1>
                <p style="margin:0 0 12px;font-size:16px;line-height:25px;color:#514E6D;">Спасибо, что выбрали WebVitrina. Нам важно убедиться, что это действительно вы, поэтому нужно подтвердить адрес электронной почты.</p>
                <p style="margin:0;font-size:16px;line-height:25px;color:#514E6D;">Это займёт меньше минуты. Подтвердите адрес, чтобы продолжить покупки, общение и работу с аккаунтом на WebVitrina.</p>
            </td>
            <td class="stack art" width="38%" align="center" valign="middle" style="width:38%;padding-left:16px;">
               <img src="{{ asset('images/email/verify-email-hero.png') }}" width="440" alt="Витрина на ноутбуке и конверт с отметкой подтверждения" style="display:block;width:100%;max-width:440px;height:auto;border:0;color:#4F46E5;font-size:14px;line-height:22px;">
            </td>
        </tr></table>
    </td></tr>
    <tr><td class="content-pad" style="padding:0 2.8% 26px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#4F46E5" style="background-color:#4F46E5;background-image:linear-gradient(110deg,#4338CA,#6D5CE7);border-radius:22px;"><tr><td class="cta-inner" style="padding:26px 2.8%;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                <td class="cta-icon" width="110" style="width:110px;padding-right:28px;"><table role="presentation" width="78" cellpadding="0" cellspacing="0"><tr><td align="center" height="78" bgcolor="#8A7DE2" style="height:78px;border-radius:22px;font-size:48px;line-height:60px;color:#FFFFFF;">&#9993;</td></tr></table></td>
                <td class="stack" width="62%" valign="middle" style="width:62%;color:#FFFFFF;border-left:1px solid #B9B0F6;padding-left:28px;">
                    <h2 style="margin:0 0 8px;font-size:24px;line-height:31px;">Остался один шаг — подтвердите email</h2>
                    <p style="margin:0;font-size:14px;line-height:22px;color:#EFEDFF;">Подтверждение поможет нам отправлять письма на верный адрес.</p>
                </td>
                <td class="stack cta-button" width="38%" align="right" valign="middle" style="width:38%;padding-left:28px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;max-width:340px;"><tr><td align="center" bgcolor="#FFFFFF" style="border-radius:30px;mso-padding-alt:17px 18px;">
                        <a href="{{ $url }}" style="display:block;padding:17px 18px;border:1px solid #FFFFFF;border-radius:30px;font-size:18px;line-height:24px;font-weight:bold;color:#4F46E5;text-decoration:none;mso-padding-alt:0;">ПОДТВЕРДИТЬ EMAIL&nbsp; &#8594;</a>
                    </td></tr></table>
                </td>
            </tr></table>
        </td></tr></table>
    </td></tr>
    <tr><td class="content-pad" style="padding:0 4.2% 16px;">
        <h2 style="margin:0 0 5px;font-size:23px;line-height:30px;">Зачем это нужно?</h2>
        <p style="margin:0;font-size:15px;line-height:23px;color:#514E6D;">Один аккаунт для покупок, общения и управления профилем.</p>
    </td></tr>
    <tr><td class="content-pad" style="padding:0 2.2% 20px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;"><tr>
                    <td class="stack benefit" width="33.33%" valign="top" style="width:33.33%;padding:0 6px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#FAFAFF" style="border:1px solid #E1DFF3;border-radius:16px;"><tr><td valign="top" width="76" style="width:76px;padding:16px 0 16px 20px;"><table role="presentation" width="58" cellpadding="0" cellspacing="0"><tr><td align="center" bgcolor="#EAE8FF" height="58" style="height:58px;border-radius:18px;font-size:30px;line-height:40px;">🏪</td></tr></table></td><td valign="top" style="padding:16px 16px 16px 12px;">
                    <h3 style="margin:0 0 7px;font-size:16px;line-height:23px;color:#23213F;">Покупки и продажи</h3>
                    <p style="margin:0;font-size:14px;line-height:22px;color:#514E6D;">Находите товары, ведите каталог и управляйте заказами на площадке.</p>
                </td></tr></table>
            </td>
                    <td class="stack benefit" width="33.33%" valign="top" style="width:33.33%;padding:0 6px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#FAFAFF" style="border:1px solid #E1DFF3;border-radius:16px;"><tr><td valign="top" width="76" style="width:76px;padding:16px 0 16px 20px;"><table role="presentation" width="58" cellpadding="0" cellspacing="0"><tr><td align="center" bgcolor="#EAE8FF" height="58" style="height:58px;border-radius:18px;font-size:30px;line-height:40px;">⭐</td></tr></table></td><td valign="top" style="padding:16px 16px 16px 12px;">
                    <h3 style="margin:0 0 7px;font-size:16px;line-height:23px;color:#23213F;">Отзывы и избранное</h3>
                    <p style="margin:0;font-size:14px;line-height:22px;color:#514E6D;">Сохраняйте понравившиеся товары и делитесь впечатлениями о покупках.</p>
                </td></tr></table>
            </td>
                    <td class="stack benefit" width="33.33%" valign="top" style="width:33.33%;padding:0 6px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#FAFAFF" style="border:1px solid #E1DFF3;border-radius:16px;"><tr><td valign="top" width="76" style="width:76px;padding:16px 0 16px 20px;"><table role="presentation" width="58" cellpadding="0" cellspacing="0"><tr><td align="center" bgcolor="#EAE8FF" height="58" style="height:58px;border-radius:18px;font-size:30px;line-height:40px;">📦</td></tr></table></td><td valign="top" style="padding:16px 16px 16px 12px;">
                    <h3 style="margin:0 0 7px;font-size:16px;line-height:23px;color:#23213F;">Личный кабинет</h3>
                    <p style="margin:0;font-size:14px;line-height:22px;color:#514E6D;">Заказы, настройки аккаунта и поддержка — всё важное в одном месте.</p>
                </td></tr></table>
            </td>
                </tr></table>
    </td></tr>
        @if(($user->role ?? null) === 'seller')
    <tr><td class="content-pad" style="padding:0 3.5% 16px;font-size:16px;line-height:25px;color:#514E6D;">Для публикации товаров и новых продаж также потребуется подтвердить личный телефон через Telegram в настройках аккаунта.</td></tr>
    @endif
    <tr><td class="content-pad" style="padding:0 2.8% 20px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#EEEDFF" style="table-layout:fixed;border-radius:14px;"><tr><td class="link-icon" width="76" align="center" style="width:76px;font-size:28px;color:#4F46E5;">&#128279;</td><td style="padding:14px 20px 14px 0;">
            <p style="margin:0 0 8px;font-size:14px;line-height:22px;color:#37316F;">Если кнопка не работает, скопируйте ссылку и откройте её в браузере:</p>
            <p style="margin:0;padding:5px 8px;background:#FFFFFF;border:1px solid #DDD9FF;border-radius:8px;font-size:12px;line-height:18px;word-break:break-all;overflow-wrap:anywhere;word-wrap:break-word;"><a href="{{ $url }}" style="color:#4F46E5;text-decoration:underline;word-break:break-all;overflow-wrap:anywhere;word-wrap:break-word;">{{ $url }}</a></p>
        </td></tr></table>
    </td></tr>
    <tr><td class="content-pad" style="padding:0 2.8% 20px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#FFFBEB" style="border:1px solid #FDE68A;border-radius:12px;"><tr><td style="padding:15px 18px;font-size:14px;line-height:22px;color:#92400E;"><span style="font-size:21px;color:#F5A500;">&#9432;</span>&nbsp;&nbsp; <strong>Если это были не вы,</strong> просто проигнорируйте письмо. Без перехода по ссылке адрес не будет подтверждён.</td></tr></table>
    </td></tr>
    <tr><td class="content-pad" style="padding:0 3.5% 20px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E1DFF3;"><tr>
            <td class="stack" valign="top" style="padding-top:18px;font-size:12px;line-height:20px;color:#79758E;">© {{ date('Y') }} WebVitrina.<br>Вы получили это письмо, потому что для аккаунта был указан этот адрес электронной почты.</td>
            <td class="stack footer-links" valign="top" align="right" style="padding-top:18px;font-size:12px;line-height:20px;"><a href="{{ $appUrl }}" style="color:#4F46E5;">WebVitrina</a>&nbsp; · &nbsp;<a href="{{ $appUrl }}/support" style="color:#4F46E5;">Поддержка</a></td>
        </tr></table>
    </td></tr>
</table>
</td></tr></table>
</body>
</html>
