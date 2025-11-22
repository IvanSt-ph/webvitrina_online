@php
    /** @var \App\Models\User $user */
    $userName  = $user->name ?? 'пользователь';
    $ip        = $ip ?? 'неизвестно';
    $agent     = $agent ?? 'неизвестное устройство';
    $location  = $location ?? 'местоположение не определено';
    $time      = $time ?? now()->format('d.m.Y H:i');
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Пароль успешно изменён — WebVitrina</title>
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
                                    <table cellpadding="0" cellspacing="0" role="presentation">
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
                                                    Уведомление безопасности аккаунта
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
                                        Пароль обновлён
                                    </span>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>

                <!-- ТЕКСТ В БАННЕРЕ -->
                <tr>
                    <td style="padding:10px 40px 30px;">
                        <table width="100%" role="presentation">
                            <tr>
                                <td valign="middle">
                                    <div style="font-size:24px;font-weight:700;line-height:1.4;">
                                        Пароль был успешно изменён 🔐
                                    </div>
                                    <div style="margin-top:6px;font-size:13px;opacity:0.9;max-width:420px;">
                                        Мы отправили это письмо, чтобы убедиться, что именно вы изменили пароль.
                                        Если действие кажется подозрительным — ниже есть подсказки, как защитить аккаунт.
                                    </div>
                                </td>

                                <!-- Статус -->
                                <td valign="middle" style="text-align:right;">
                                    <table cellpadding="0" cellspacing="0" role="presentation"
                                           style="display:inline-block;background:rgba(15,23,42,0.18);
                                                  border-radius:16px;padding:10px 14px;
                                                  border:1px solid rgba(255,255,255,0.2);">
                                        <tr>
                                            <td style="font-size:11px;opacity:0.85;">Статус операции</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-top:4px;">
                                                <span style="display:inline-block;width:8px;height:8px;
                                                             border-radius:50%;background:#34d399;
                                                             box-shadow:0 0 0 3px rgba(52,211,153,0.4);
                                                             margin-right:6px;"></span>
                                                <span style="font-size:12px;font-weight:600;">
                                                    Пароль успешно изменён
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


    <!-- ПРИВЕТСТВИЕ -->
    <tr>
        <td style="padding:22px 36px 6px;">
            <table width="100%" role="presentation">
                <tr>
                    <td style="color:#111827;font-size:18px;line-height:1.7;">
                        <p style="margin:0 0 14px;">
                            Привет, <strong>{{ $userName }}</strong> 👋
                        </p>

                        <p style="margin:0 0 10px;">
                            Мы зафиксировали изменение пароля для вашего аккаунта WebVitrina
                            <strong>{{ $time }}</strong>.
                        </p>

                        <p style="margin:0;">
                            Ниже — краткая информация об этом событии. Если всё выглядит знакомо,
                            дополнительных действий от вас не требуется.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>


    <!-- КАРТОЧКА С ДАННЫМИ ОБ УСТРОЙСТВЕ -->
    <tr>
        <td style="padding:10px 40px;">
            <table width="100%" style="border-radius:22px;background:#f9fafb;border:1px solid #e5e7eb;" role="presentation">
                <tr>
                    <td style="padding:16px 20px;font-size:14px;color:#6b7280;line-height:1.7;">

                        <div style="font-size:13px;font-weight:600;color:#4b5563;margin-bottom:6px;">
                            Детали изменения пароля:
                        </div>

                        <div style="margin-bottom:4px;">
                            <strong>IP-адрес:</strong> {{ $ip }}
                        </div>

                        <div style="margin-bottom:4px;">
                            <strong>Примерное местоположение:</strong> {{ $location }}
                        </div>

                        <div style="margin-bottom:4px;">
                            <strong>Устройство / браузер:</strong> {{ $agent }}
                        </div>

                        <div style="margin-top:10px;font-size:12px;color:#9ca3af;line-height:1.5;">
                            Эти данные носят информационный характер и помогают вам определить,
                            вы ли выполняли это действие.
                        </div>

                    </td>
                </tr>
            </table>
        </td>
    </tr>


    <!-- ДВА БЛОКА: ЧТО МЫ СДЕЛАЛИ / ЧТО РЕКОМЕНДУЕМ -->
    <tr>
        <td style="padding:8px 40px 4px;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>

                    <!-- ЧТО МЫ СДЕЛАЛИ -->
                    <td valign="top" style="padding:8px 8px 12px;">
                        <table width="100%" style="border-radius:18px;background:#f9fafb;border:1px solid #e5e7eb;" role="presentation">
                            <tr>
                                <td style="padding:14px 16px;font-size:13px;color:#4b5563;line-height:1.6;">
                                    <div style="font-weight:600;margin-bottom:6px;">
                                        Что мы уже сделали ✅
                                    </div>
                                    <ul style="margin:0;padding-left:18px;">
                                        <li style="margin-bottom:4px;">
                                            Обновили ваш пароль в системе.
                                        </li>
                                        <li style="margin-bottom:4px;">
                                            Обновили токен сессии для повышения безопасности.
                                        </li>
                                        <li>
                                            Сохранили информацию об этом событии в журнале активности.
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <!-- ЧТО РЕКОМЕНДУЕМ -->
                    <td valign="top" style="padding:8px 8px 12px;">
                        <table width="100%" style="border-radius:18px;background:#fefce8;border:1px solid #facc15;" role="presentation">
                            <tr>
                                <td style="padding:14px 16px;font-size:13px;color:#78350f;line-height:1.6;">
                                    <div style="font-weight:600;margin-bottom:6px;">
                                        Что мы рекомендуем ⚠️
                                    </div>
                                    <ul style="margin:0;padding-left:18px;">
                                        <li style="margin-bottom:4px;">
                                            Если вы не уверены, что меняли пароль сами —
                                            немедленно выполните восстановление доступа.
                                        </li>
                                        <li style="margin-bottom:4px;">
                                            Не передавайте пароль третьим лицам и не сохраняйте его в открытом виде.
                                        </li>
                                        <li>
                                            Для важных аккаунтов используйте уникальные, сложные пароли.
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </table>
                    </td>

                </tr>
            </table>
        </td>
    </tr>


    <!-- CTA -->
    <tr>
        <td style="padding:18px 40px 10px;">
            <table width="100%" style="border-radius:22px;
                                      background:linear-gradient(135deg,#ef4444,#f43f5e);
                                      color:#ffffff;" role="presentation">
                <tr>
                    <td style="padding:20px 28px 16px;text-align:center;">

                        <div style="font-size:20px;font-weight:700;margin-bottom:10px;">
                            Если это были не вы
                        </div>

                        <div style="font-size:15px;opacity:0.92;line-height:1.6;margin-bottom:14px;">
                            Немедленно восстановите пароль и по возможности смените его и в других сервисах,
                            где он мог совпадать.
                        </div>

                        <a href="{{ url('/forgot-password') }}"
                           style="display:block;max-width:380px;margin:0 auto;
                                  padding:16px 0;border-radius:999px;
                                  background:#ffffff;color:#ef4444;
                                  font-size:16px;font-weight:800;text-decoration:none;
                                  letter-spacing:0.06em;text-transform:uppercase;
                                  box-shadow:0 12px 30px rgba(15,23,42,0.35);">
                            Восстановить доступ
                        </a>

                        <div style="font-size:12px;opacity:0.85;line-height:1.6;margin-top:12px;">
                            После восстановления пароля мы рекомендуем выйти из аккаунта
                            на всех устройствах и войти заново.
                        </div>

                    </td>
                </tr>
            </table>
        </td>
    </tr>


    <!-- ПОДСКАЗКА / ПАМЯТКА -->
    <tr>
        <td style="padding:10px 36px 10px;">
            <table width="100%" style="border-radius:16px;background:#f9fafb;border:1px solid #e5e7eb;" role="presentation">
                <tr>
                    <td style="padding:12px 16px;font-size:12px;color:#6b7280;line-height:1.6;">
                        <strong>Мы никогда не отправляем ваш пароль в письмах.</strong><br>
                        Если вы видите сообщения, где от вашего имени просят сообщить пароль
                        или код из SMS — это мошенники. Не отвечайте на такие письма и не переходите
                        по подозрительным ссылкам.
                    </td>
                </tr>
            </table>
        </td>
    </tr>


    <!-- ФУТЕР -->
    <tr>
        <td style="padding:14px 34px 18px;background:#f9fafb;border-top:1px solid #e5e7eb;">
            <table width="100%" role="presentation">
                <tr>

                    <td style="font-size:11px;color:#9ca3af;line-height:1.6;">
                        © {{ date('Y') }} WebVitrina. Мы заботимся о вашей безопасности.
                        <br>
                        Это письмо отправлено автоматически и не требует ответа.
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
