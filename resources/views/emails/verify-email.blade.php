@php
    /** @var \App\Models\User $user */
    $userName = $user->name ?? 'друг';
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Подтверждение email — WebVitrina</title>
</head>

<body style="margin:0;padding:0;background:#f2f3fb;
             font-family:'Inter', -apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;">

<!-- Внешний фон -->
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f2f3fb;">
<tr>
<td align="center" style="padding:26px 6px;">

<!-- ОСНОВНОЙ КОНТЕЙНЕР -->
<table width="100%" cellpadding="0" cellspacing="0" role="presentation"
       style="max-width:880px;background:#ffffff;border-radius:28px;overflow:hidden;
              box-shadow:0 26px 70px rgba(15,23,42,0.16);">

    <!-- ВЕРХНИЙ БАННЕР -->
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                   style="background:linear-gradient(120deg,#4f46e5,#6366f1,#8b5cf6,#ec4899);color:#ffffff;">

                <!-- ЛОГО И ЗАГОЛОВОК -->
                <tr>
                    <td style="padding:20px 30px 18px;">
                        <table width="100%" cellpadding="0" cellspacing="0">

                            <tr>
                                <!-- ЛОГО -->
                                <td valign="middle">
                                    <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td valign="middle" style="padding-right:12px;">
                                                <img src="{{ asset('images/icon.png') }}"
                                                     alt="WebVitrina"
                                                     style="display:block;height:40px;width:40px;
                                                            border-radius:12px;
                                                            box-shadow:0 4px 12px rgba(0,0,0,0.25);">
                                            </td>
                                            <td valign="middle">
                                                <div style="font-size:18px;font-weight:700;letter-spacing:0.4px;">
                                                    WebVitrina
                                                </div>
                                                <div style="font-size:11px;opacity:0.9;">
                                                    Маркетплейс, где товары находят своих людей
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>

                                <!-- ТЭГ -->
                                <td valign="middle" style="text-align:right;">
                                    <span style="display:inline-block;padding:6px 12px;border-radius:999px;
                                                 background:rgba(15,23,42,0.22);
                                                 border:1px solid rgba(255,255,255,0.25);
                                                 font-size:11px;font-weight:500;">
                                        Подтверждение email
                                    </span>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>

                <!-- ТЕКСТ В БАННЕРЕ -->
                <tr>
                    <td style="padding:10px 40px 30px;">
                        <table width="100%">
                            <tr>
                                <td valign="middle">
                                    <div style="font-size:24px;font-weight:700;line-height:1.4;">
                                        Ваш аккаунт почти готов 🚀
                                    </div>
                                    <div style="margin-top:6px;font-size:13px;opacity:0.9;max-width:420px;">
                                        Осталось подтвердить почту — и вы сможете полноценно
                                        пользоваться всеми возможностями WebVitrina.
                                    </div>
                                </td>

                                <!-- Статус -->
                                <td valign="middle" style="text-align:right;">
                                    <table cellpadding="0" cellspacing="0" role="presentation"
                                           style="display:inline-block;background:rgba(15,23,42,0.18);
                                                  border-radius:16px;padding:10px 14px;
                                                  border:1px solid rgba(255,255,255,0.2);">
                                        <tr>
                                            <td style="font-size:11px;opacity:0.85;">Статус аккаунта</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-top:4px;">
                                                <span style="display:inline-block;width:8px;height:8px;
                                                             border-radius:50%;background:#facc15;
                                                             box-shadow:0 0 0 3px rgba(250,204,21,0.4);
                                                             margin-right:6px;"></span>
                                                <span style="font-size:12px;font-weight:600;">
                                                    Требуется подтверждение
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>

                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>


    <!-- БЛОК ПРИВЕТСТВИЯ -->
    <tr>
        <td style="padding:22px 36px 6px;">
            <table width="100%">
                <tr>
                    <td style="color:#111827;font-size:18px;line-height:1.7;">
                        <p style="margin:0 0 14px;">
                            Привет, <strong>{{ $userName }}</strong> 👋
                        </p>

                        <p style="margin:0 0 14px;">
                            Спасибо, что выбрали <strong>WebVitrina</strong>.
                            Нам важно убедиться, что это действительно вы, поэтому
                            нужно подтвердить адрес электронной почты.
                        </p>

                        <p style="margin:0;">
                            Это займёт меньше минуты, зато ваш аккаунт будет защищён,
                            а уведомления о заказах и сообщениях будут приходить на верный адрес.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>


    <!-- БЛОК 3 КАРТОЧКИ -->
    <tr>
        <td style="padding:10px 40px;">
            <table width="100%" style="border-radius:22px;background:#f9fafb;border:1px solid #e5e7eb;">

                <!-- Заголовок блока -->
                <tr>
                    <td style="padding:14px 18px 6px;">
                        <div style="font-size:14px;font-weight:600;color:#4b5563;
                                    letter-spacing:0.06em;text-transform:uppercase;margin-bottom:6px;">
                            Зачем это нужно?
                        </div>
                        <div style="font-size:16px;font-weight:600;color:#111827;">
                            Что изменится после подтверждения email:
                        </div>
                    </td>
                </tr>

                <!-- Карточки -->
                <tr>
                    <td style="padding:4px 10px 20px;">
                        <table width="100%">
                            <tr>

                                <!-- 1 -->
                                <td valign="top" width="33%" style="padding:10px;">
                                    <table width="100%" style="border-radius:16px;background:#ffffff;
                                                              border:1px solid #e5e7eb;">
                                        <tr>
                                            <td style="padding:10px 12px 6px;">
                                                <div style="width:34px;height:34px;border-radius:12px;
                                                            background:rgba(59,130,246,0.1);
                                                            text-align:center;line-height:34px;font-size:18px;">
                                                    🛒
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0 12px 8px;">
                                                <div style="font-size:14px;font-weight:600;margin-bottom:4px;">
                                                    Заказы без ограничений
                                                </div>
                                                <div style="font-size:12px;color:#6b7280;line-height:1.5;">
                                                    Оформляйте покупки, отслеживайте статусы заказов
                                                    и историю в личном кабинете.
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>

                                <!-- 2 -->
                                <td valign="top" width="33%" style="padding:10px;">
                                    <table width="100%" style="border-radius:16px;background:#ffffff;
                                                              border:1px solid #e5e7eb;">
                                        <tr>
                                            <td style="padding:10px 12px 6px;">
                                                <div style="width:34px;height:34px;border-radius:12px;
                                                            background:rgba(16,185,129,0.1);
                                                            text-align:center;line-height:34px;font-size:18px;">
                                                    ⭐
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0 12px 10px;">
                                                <div style="font-size:14px;font-weight:600;margin-bottom:4px;">
                                                    Отзывы и избранное
                                                </div>
                                                <div style="font-size:12px;color:#6b7280;line-height:1.5;">
                                                    Добавляйте товары в избранное и оставляйте отзывы продавцам.
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>

                                <!-- 3 -->
                                <td valign="top" width="33%" style="padding:10px;">
                                    <table width="100%" style="border-radius:16px;background:#ffffff;
                                                              border:1px solid #e5e7eb;">
                                        <tr>
                                            <td style="padding:10px 12px 6px;">
                                                <div style="width:34px;height:34px;border-radius:12px;
                                                            background:rgba(168,85,247,0.1);
                                                            text-align:center;line-height:34px;font-size:18px;">
                                                    📦
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0 12px 10px;">
                                                <div style="font-size:14px;font-weight:600;margin-bottom:4px;">
                                                    Кабинет продавца
                                                </div>
                                                <div style="font-size:12px;color:#6b7280;line-height:1.5;">
                                                    Управляйте витриной, заказами и статистикой (если вы продавец).
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>

                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>


    <!-- CTA — КОНПКА -->
    <tr>
        <td style="padding:18px 40px 10px;">
            <table width="100%" style="border-radius:22px;
                                      background:linear-gradient(135deg,#4f46e5,#6366f1,#8b5cf6);
                                      color:#ffffff;">
                <tr>
                    <td style="padding:20px 28px 16px;">
                        <table width="100%">

                            <tr>
                                <td style="text-align:center;">
                                    <div style="font-size:22px;font-weight:700;margin-bottom:10px;">
                                        Остался один шаг — подтвердите свой email
                                    </div>
                                    <div style="font-size:15px;opacity:0.92;line-height:1.6;">
                                        После подтверждения вы получите полный доступ —
                                        от заказов и избранного до отзывов и кабинета продавца.
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding-top:10px;text-align:center;">
                                    <a href="{{ $url }}"
                                       style="display:block;max-width:380px;margin:0 auto;
                                              padding:16px 0;border-radius:999px;
                                              background:#ffffff;color:#4f46e5;
                                              font-size:18px;font-weight:800;text-decoration:none;
                                              letter-spacing:0.06em;text-transform:uppercase;
                                              box-shadow:0 12px 30px rgba(15,23,42,0.35);">
                                        Подтвердить email
                                    </a>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>


    <!-- ПОДСКАЗКА -->
    <tr>
        <td style="padding:14px 36px 8px;">
            <table width="100%">
                <tr>
                    <td style="font-size:13px;color:#6b7280;line-height:1.6;">
                        Если кнопка не работает, скопируйте ссылку ниже:
                        <br><br>
                        <span style="display:block;background:#f3f4f6;border:1px solid #e5e7eb;
                                     border-radius:10px;padding:10px 12px;font-size:12px;
                                     color:#4b5563;word-break:break-all;">
                            {{ $url }}
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>


    <!-- БЕЗОПАСНОСТЬ -->
    <tr>
        <td style="padding:10px 36px 20px;">
            <table width="100%" style="border-radius:18px;background:#fffbeb;border:1px solid #facc15;">
                <tr>
                    <td style="padding:14px 18px;">

                        <table width="100%">
                            <tr>
                                <td valign="top" style="width:26px;">
                                    <span style="display:inline-block;width:22px;height:22px;
                                                 border-radius:999px;background:#fef3c7;
                                                 text-align:center;line-height:22px;font-size:14px;">
                                        ⚠️
                                    </span>
                                </td>
                                <td valign="top" style="font-size:13px;color:#92400e;line-height:1.6;">
                                    <strong>Если это были не вы.</strong><br>
                                    Просто игнорируйте это письмо — email не будет привязан к аккаунту.
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>
        </td>
    </tr>


    <!-- ФУТЕР -->
    <tr>
        <td style="padding:14px 34px 18px;background:#f9fafb;border-top:1px solid #e5e7eb;">
            <table width="100%">
                <tr>

                    <td style="font-size:11px;color:#9ca3af;line-height:1.6;">
                        © {{ date('Y') }} WebVitrina. Все права защищены.
                        <br>
                        Вы получили это письмо, потому что указали этот email
                        при регистрации или входе через Google.
                    </td>

                    <td style="text-align:right;font-size:11px;color:#9ca3af;">
                        <a href="{{ config('app.url') }}"
                           style="color:#818cf8;text-decoration:none;font-weight:500;">
                            О WebVitrina
                        </a>
                        <span style="margin:0 8px;">|</span>
                        <a href="{{ config('app.url') }}/support"
                           style="color:#818cf8;text-decoration:none;font-weight:500;">
                            Поддержка
                        </a>
                    </td>

                </tr>
            </table>
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>
