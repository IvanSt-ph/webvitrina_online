@php
    /** @var \App\Models\User $user */
    $userName = $user->name ?? 'друг';
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Восстановление пароля — WebVitrina</title>
</head>

<body style="margin:0;padding:0;background:#f2f3fb;
             font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;">

<!-- Внешний фон -->
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f2f3fb;">
<tr>
<td align="center" style="padding:26px 6px;">

<!-- КОНТЕЙНЕР -->
<table width="100%" cellpadding="0" cellspacing="0" role="presentation"
       style="max-width:880px;background:#ffffff;border-radius:28px;overflow:hidden;
              box-shadow:0 26px 70px rgba(15,23,42,0.16);">

    <!-- ГРАДИЕНТНЫЙ ХЕДЕР -->
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:linear-gradient(120deg,#4f46e5,#6366f1,#8b5cf6,#ec4899);color:#ffffff;">

                <tr>
                    <td style="padding:20px 30px 18px;">
                        <table width="100%">
                            <tr>
                                <!-- ЛОГО -->
                                <td valign="middle">
                                    <table>
                                        <tr>
                                            <td style="padding-right:12px;">
                                                <img src="{{ asset('images/icon.png') }}"
                                                     style="height:40px;width:40px;border-radius:12px;
                                                            box-shadow:0 4px 12px rgba(0,0,0,0.25);">
                                            </td>
                                            <td>
                                                <div style="font-size:18px;font-weight:700;">
                                                    WebVitrina
                                                </div>
                                                <div style="font-size:11px;opacity:0.9;">
                                                    Маркетплейс, где товары находят своих людей
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>

                                <!-- ТЕГ -->
                                <td valign="middle" style="text-align:right;">
                                    <span style="padding:6px 12px;border-radius:999px;
                                                 background:rgba(15,23,42,0.22);
                                                 border:1px solid rgba(255,255,255,0.25);
                                                 font-size:11px;">
                                        Восстановление пароля
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- ТЕКСТ -->
                <tr>
                    <td style="padding:10px 40px 30px;">
                        <div style="font-size:24px;font-weight:700;line-height:1.4;">
                            Запрос на смену пароля 🔐
                        </div>
                        <div style="margin-top:6px;font-size:13px;opacity:0.9;max-width:420px;">
                            Кто-то (возможно, вы) запросил восстановление пароля для вашего аккаунта.
                            Если это были вы — нажмите кнопку ниже.
                        </div>
                    </td>
                </tr>

            </table>
        </td>
    </tr>


    <!-- ПРИВЕТСТВИЕ -->
    <tr>
        <td style="padding:22px 36px 6px;">
            <div style="color:#111827;font-size:18px;line-height:1.7;">
                Привет, <strong>{{ $userName }}</strong> 👋<br><br>
                Чтобы установить новый пароль, просто перейдите по ссылке ниже.
                Эта ссылка будет действительна ограниченное время.
            </div>
        </td>
    </tr>


    <!-- КНОПКА -->
    <tr>
        <td style="padding:18px 40px 10px;">
            <table width="100%" style="border-radius:22px;
                                      background:linear-gradient(135deg,#4f46e5,#6366f1,#8b5cf6);
                                      color:#ffffff;">

                <tr>
                    <td style="padding:20px 28px 16px;text-align:center;">

                        <div style="font-size:22px;font-weight:700;margin-bottom:10px;">
                            Установите новый пароль
                        </div>

                        <a href="{{ $url }}"
                           style="display:block;max-width:380px;margin:0 auto;
                                  padding:16px 0;border-radius:999px;
                                  background:#ffffff;color:#4f46e5;
                                  font-size:18px;font-weight:800;text-decoration:none;
                                  letter-spacing:0.06em;text-transform:uppercase;
                                  box-shadow:0 12px 30px rgba(15,23,42,0.35);">
                            Сбросить пароль
                        </a>

                        <div style="font-size:14px;opacity:0.92;margin-top:12px;">
                            Если вы не запрашивали сброс пароля — просто
                            проигнорируйте это письмо.
                        </div>

                    </td>
                </tr>

            </table>
        </td>
    </tr>


    <!-- ЕСЛИ КНОПКА НЕ РАБОТАЕТ -->
    <tr>
        <td style="padding:14px 36px 8px;">
            <div style="font-size:13px;color:#6b7280;line-height:1.6;">
                Если кнопка не работает, используйте ссылку ниже:
                <br><br>
                <span style="display:block;background:#f3f4f6;border:1px solid #e5e7eb;
                             border-radius:10px;padding:10px 12px;font-size:12px;
                             color:#4b5563;word-break:break-all;">
                    {{ $url }}
                </span>
            </div>
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
                        Если вы не запрашивали это действие — просто удалите письмо.
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

</td></tr></table>
</body>
</html>
